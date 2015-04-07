<?php

namespace Apis;

/**
 * Implements naive API caching using `openclerk/cache`.
 */
abstract class CachedApi extends Api {

  /**
   * Return a 32-character hash from the given argmuents so that it
   * can be cached.
   */
  abstract function getHash($arguments);

  /**
   * How long are API calls cached?
   * @return number of seconds, default 60
   */
  function getAge() {
    return 60;
  }

  /**
   * Try and get the JSON result for this API, and return either
   * `{success: true: result: $json}` or
   * `{success: false, error: $message}` if an exception occured.
   *
   * This means this {@link Api} can be used directly with `openclerk/routing`
   * as a route callback:
   * <pre>
   * foreach (DiscoveredComponents\Apis::getAllInstances() as $uri => $handler) {
   *   \Openclerk\Router::addRoutes(array(
   *     $uri => $handler,
   *   ));
   * }
   * </pre>
   *
   * If openclerk/exceptions is installed, logs a new uncaught
   * {@link CaughtApiException} if there was an exception that occured.
   *
   * Caching can be achieved with a {@link CachedApi}.
   */
  function render($arguments) {
    header("Content-Type: application/json");
    header("Access-Control-Allow-Origin: *");
    echo $this->renderJSON($arguments);
  }

  function renderJSON($arguments) {
    try {

      return \Openclerk\Cache::get(db(),
        $this->getEndpoint() /* key */,
        $this->getHash($arguments) /* hash */,
        $this->getAge() /* seconds */,
        array($this, 'getCached'),
        array($arguments));

    } catch (\Exception $e) {
      // render an API exception
      // we wrap exceptions here, not in getCached(), because we don't want to be
      // caching exceptions or errors
      // TODO use an error HTTP code
      $json = array('success' => false, 'error' => $e->getMessage(), 'time' => date('c'));
      return json_encode($json);

      if (function_exists('log_uncaught_exception')) {
        log_uncaught_exception(new CaughtApiException("API threw '" . $e->getMessage() . "'", $e));
      }
    }
  }

  function getCached($arguments) {
    $json = array('success' => true, 'result' => $this->getJSON($arguments), 'time' => date('c'));
    return json_encode($json);
  }

}
