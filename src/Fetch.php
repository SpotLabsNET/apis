<?php

namespace Apis;

class Fetch {

  /**
   * Wraps {@link #file_get_contents()} with timeout information etc.
   * May throw a {@link ExternalAPIException} if something unexpected occured.
   */
  static function get($url, $options = array()) {
    // normally file_get_contents is OK, but if URLs are down etc, the timeout has no value and we can just stall here forever
    // this also means we don't have to enable OpenSSL on windows for file_get_contents('https://...'), which is just a bit of a mess
    $ch = self::initCurl();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; Openclerk PHP client; '.php_uname('s').'; PHP/'.phpversion().')');
    curl_setopt($ch, CURLOPT_URL, $url);
    // curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    // curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_ENCODING, "gzip,deflate");     // enable gzip decompression if necessary
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    foreach ($options as $key => $value) {
      curl_setopt($ch, $key, $value);
    }

    // run the query
    $res = curl_exec($ch);

    if ($res === false) throw new ExternalAPIException('Could not get reply: ' . curl_error($ch));
    self::checkResponse($res);

    return $res;
  }

  /**
   * Extends {@link #curl_init()} to also set {@code CURLOPT_TIMEOUT}
   * and {@code CURLOPT_CONNECTTIMEOUT} appropriately.
   */
  static function initCurl() {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_TIMEOUT, \Openclerk\Config::get('get_contents_timeout') /* in sec */);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, \Openclerk\Config::get('get_contents_timeout') /* in sec */);
    return $ch;
  }

  /**
   * @throws a {@link CloudFlareException} or {@link IncapsulaException} if the given
   *    remote response suggests something about CloudFlare or Incapsula.
   * @throws an {@link ExternalAPIException} if the response suggests something else that was unexpected
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
      throw new ExternalAPIException("API location has been moved permanently" . ($message ? " $message" : ""));
    }
    if (strpos($string, "Access denied for user '") !== false) {
      throw new ExternalAPIException("Remote database host returned 'Access denied'" . ($message ? " $message" : ""));
    }
    if (strpos(strtolower($string), "502 bad gateway") !== false) {
      throw new ExternalAPIException("Bad gateway" . ($message ? " $message" : ""));
    }
    if (strpos(strtolower($string), "503 service unavailable") !== false) {
      throw new ExternalAPIException("Service unavailable" . ($message ? " $message" : ""));
    }
    if (strpos(strtolower($string), "connection timed out") !== false) {
      throw new ExternalAPIException("Connection timed out" . ($message ? " $message" : ""));
    }
  }

  /**
   * Try to decode a JSON string, or try and work out why it failed to decode but throw an exception
   * if it was not a valid JSON string.
   *
   * @param empty_is_ok if true, then don't bail if the returned JSON is an empty array
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
        throw new ExternalAPIException("Unexpectedly received HTML instead of JSON" . ($message ? " $message" : ""));
      }
      if (strpos(strtolower($string), "invalid key") !== false) {
        throw new ExternalAPIException("Invalid key" . ($message ? " $message" : ""));
      }
      if (strpos(strtolower($string), "bad api key") !== false) {
        throw new ExternalAPIException("Bad API key" . ($message ? " $message" : ""));
      }
      if (strpos(strtolower($string), "access denied") !== false) {
        throw new ExternalAPIException("Access denied" . ($message ? " $message" : ""));
      }
      if (strpos(strtolower($string), "parameter error") !== false) {
        // for 796 Exchange
        throw new ExternalAPIException("Parameter error" . ($message ? " $message" : ""));
      }
      if (!$string) {
        throw new EmptyResponseException('Response was empty' . ($message ? " $message" : ""));
      }
      throw new ExternalAPIException('Invalid data received' . ($message ? " $message" : ""));
    }
    return $json;
  }

}
