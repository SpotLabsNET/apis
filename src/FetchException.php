<?php

namespace Apis;

/**
 * Represents something bad occured while trying to fetch an external API.
 */
class FetchException extends \Exception {
  function __construct($message, \Exception $previous = null) {
    parent::__construct($message, 0, $previous);
  }
}
