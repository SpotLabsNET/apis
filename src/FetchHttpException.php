<?php

namespace Apis;

/**
 * Represents an HTTP error code that was returned during execution.
 * This also provides access to the content returned by the server too.
 */
class FetchHttpException extends FetchException {
  var $content;

  function __construct($message, $content, \Exception $previous = null) {
    parent::__construct($message, $previous);
    $this->content = $content;
  }

  function getContent() {
    return $this->content;
  }
}
