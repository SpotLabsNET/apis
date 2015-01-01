<?php

namespace Apis\Tests;

use Apis\Fetch;
use Apis\JSendException;

class FetchTest extends \PHPUnit_Framework_TestCase {

  function testJSend() {
    $json = array("status" => "success", "data" => array("a" => "b"));
    $this->assertEquals(array("a" => "b"), Fetch::checkJSend($json));
  }

  function testJSendFailure() {
    $json = array("status" => "status", "message" => "Something happened");
    try {
      Fetch::checkJSend($json);
      $this->fail("Expected failure");
    } catch (JSendException $e) {
      // expected
    }
  }

}
