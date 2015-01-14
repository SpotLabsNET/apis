<?php

namespace Apis;

abstract class Api {

  /**
   * Compile the JSON with the given arguments, as parsed
   * from the {@link #getEndpoint()} string.
   */
  abstract function getJSON($arguments);

  /**
   * @return e.g. "/api/v1/currency/:currency"
   */
  abstract function getEndpoint();

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
   * TODO caching
   */
  function render($arguments) {
    header("Content-Type: application/json");

    try {
      $json = array('success' => true, 'result' => $this->getJSON($arguments));
      echo json_encode($json);

    } catch (\Exception $e) {
      // render an API exception
      // TODO use an error HTTP code
      $json = array('success' => false, 'error' => $e->getMessage());
      echo json_encode($json);

      if (function_exists('log_uncaught_exception')) {
        log_uncaught_exception(new CaughtApiException("API threw '" . $e->getMessage() . "'", $e));
      }
    }
  }

}
