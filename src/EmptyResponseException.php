<?php

namespace Apis;

/**
 * Represents that we actually just got an empty exception.
 */
class EmptyResponseException extends FetchException {
  function __construct($message, \Exception $previous = null) {
    parent::__construct($message, 0, $previous);
  }
}
