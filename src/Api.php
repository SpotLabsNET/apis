<?php

namespace Apis;

abstract class Api {

  abstract function getJSON($arguments);

  abstract function getEndpoint();

  // TODO caching
  function render($arguments) {
    header("Content-Type: application/json");

    try {
      $json = array('success' => true, 'result' => $this->getJSON($arguments));
      echo json_encode($json);

    } catch (\Exception $e) {
      // render an API exception
      $json = array('success' => false, 'error' => $e->getMessage());
      echo json_encode($json);

      if (function_exists('log_uncaught_exception')) {
        log_uncaught_exception(new CaughtApiException("API threw '" . $e->getMessage() . "'", $e));
      }
    }
  }

}
