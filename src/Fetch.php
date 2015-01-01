<?php

namespace Apis;

use \Openclerk\Config;

class Fetch {

  /**
   * Wraps {@link #file_get_contents()} with timeout information etc.
   * Actually uses {@code curl} to do the fetch.
   * Optionally uses the user agent defined in Config::get('fetch_user_agent').
   *
   * TODO currently sets CURLOPT_SSL_VERIFYPEER to FALSE globally; this should be an option
   *
   * @param $options additional CURL options to pass
   * @throws a {@link FetchException} if something unexpected occured
   */
  static function get($url, $options = array()) {
    // normally file_get_contents is OK, but if URLs are down etc, the timeout has no value and we can just stall here forever
    // this also means we don't have to enable OpenSSL on windows for file_get_contents('https://...'), which is just a bit of a mess
    $ch = self::initCurl();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; ' . Config::get('fetch_user_agent', 'openclerk/api PHP fetch') . ' '.php_uname('s').'; PHP/'.phpversion().')');
    curl_setopt($ch, CURLOPT_URL, $url);
    // curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    // curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_ENCODING, "gzip,deflate");     // enable gzip decompression if necessary

    // TODO should this actually be set to true? or a fetch option?
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    foreach ($options as $key => $value) {
      curl_setopt($ch, $key, $value);
    }

    // run the query
    $res = curl_exec($ch);

    if ($res === false) throw new FetchException('Could not get reply: ' . curl_error($ch));
    self::checkResponse($res);

    return $res;
  }

  /**
   * Extends {@link #curl_init()} to also set {@code CURLOPT_TIMEOUT}
   * and {@code CURLOPT_CONNECTTIMEOUT} appropriately.
   * These are set based on Config::get('get_contents_timeout') and Config::get('get_contents_timeout')
   */
  static function initCurl() {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_TIMEOUT, Config::get('get_contents_timeout') /* in sec */);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, Config::get('get_contents_timeout') /* in sec */);
    return $ch;
  }

  /**
   * @throws a {@link CloudFlareException} or {@link IncapsulaException} if the given
   *    remote response suggests something about CloudFlare or Incapsula
   * @throws an {@link FetchException} if the response suggests something else that was unexpected
   */
  static function checkResponse($string, $message = false) {
    if (strpos($string, 'DDoS protection by CloudFlare') !== false) {
      throw new CloudFlareException('Throttled by CloudFlare' . ($message ? " $message" : ""));
    }
    if (strpos($string, 'CloudFlare') !== false) {
      if (strpos($string, 'The origin web server timed out responding to this request.') !== false) {
        throw new CloudFlareException('CloudFlare reported: The origin web server timed out responding to this request.');
      }
      if (strpos($string, 'Web server is down') !== false) {
        throw new CloudFlareException('CloudFlare reported: Web server is down.');
      }
    }
    if (strpos($string, 'Incapsula incident') !== false) {
      throw new IncapsulaException('Blocked by Incapsula' . ($message ? " $message" : ""));
    }
    if (strpos($string, '_Incapsula_Resource') !== false) {
      throw new IncapsulaException('Throttled by Incapsula' . ($message ? " $message" : ""));
    }
    if (strpos(strtolower($string), '301 moved permanently') !== false) {
      throw new FetchException("API location has been moved permanently" . ($message ? " $message" : ""));
    }
    if (strpos($string, "Access denied for user '") !== false) {
      throw new FetchException("Remote database host returned 'Access denied'" . ($message ? " $message" : ""));
    }
    if (strpos(strtolower($string), "502 bad gateway") !== false) {
      throw new FetchException("Bad gateway" . ($message ? " $message" : ""));
    }
    if (strpos(strtolower($string), "503 service unavailable") !== false) {
      throw new FetchException("Service unavailable" . ($message ? " $message" : ""));
    }
    if (strpos(strtolower($string), "connection timed out") !== false) {
      throw new FetchException("Connection timed out" . ($message ? " $message" : ""));
    }
  }

  /**
   * Try to decode a JSON string, or try and work out why it failed to decode but throw an exception
   * if it was not a valid JSON string.
   *
   * @param $empty_array_is_ok if true, then don't bail if the returned JSON is an empty array
   * @throws a {@link FetchException} if the JSON is not valid and empty_array_is_ok is false
   */
  static function jsonDecode($string, $message = false, $empty_array_is_ok = false) {
    $json = json_decode($string, true);
    if (!$json) {
      if ($empty_array_is_ok && is_array($json)) {
        // the result is an empty array
        return $json;
      }
      self::checkResponse($string);
      if (substr($string, 0, 1) == "<") {
        throw new FetchException("Unexpectedly received HTML instead of JSON" . ($message ? " $message" : ""));
      }
      if (strpos(strtolower($string), "invalid key") !== false) {
        throw new FetchException("Invalid key" . ($message ? " $message" : ""));
      }
      if (strpos(strtolower($string), "bad api key") !== false) {
        throw new FetchException("Bad API key" . ($message ? " $message" : ""));
      }
      if (strpos(strtolower($string), "access denied") !== false) {
        throw new FetchException("Access denied" . ($message ? " $message" : ""));
      }
      if (strpos(strtolower($string), "parameter error") !== false) {
        // for 796 Exchange
        throw new FetchException("Parameter error" . ($message ? " $message" : ""));
      }
      if (!$string) {
        throw new EmptyResponseException('Response was empty' . ($message ? " $message" : ""));
      }
      throw new FetchException('Invalid data received' . ($message ? " $message" : ""));
    }
    return $json;
  }

  /**
   * Checks the JSON to make sure it adheres to the JSend format http://labs.omniti.com/labs/jsend.
   * Throws an JSendException if the JSON returned a 'fail', otherwise returns the wrapped data.
   *
   * @return $json['data'] if there were no problems
   * @throws JSendException if there was a failure in the response
   */
  static function checkJSend($json) {
    if (isset($json['status'])) {
      if ($json['status'] == 'fail') {
        if (isset($json['message']) && $json['message']) {
          throw new JSendException("API failed: " . $json['message']);
        }
        if (isset($json['data'])) {
          throw new JSendException("API failed: " . implode(", ", $json['data']));
        }
        throw new JSendException("API failed with no message");
      }
    }
    if (isset($json['data'])) {
      return $json['data'];
    }
    throw new JSendException("Empty JSend response");
  }


}
