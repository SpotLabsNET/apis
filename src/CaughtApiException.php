<?php

namespace Apis;

/**
 * This wraps any other type of {@link Exception} that occurs
 * when trying to execute/process our own {@link API} endpoint.
 *
 * @see Api
 */
class CaughtApiException extends \Exception {
  function __construct($message, \Exception $previous = null) {
    parent::__construct($message, 0, $previous);
  }
}
