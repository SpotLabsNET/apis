<?php

namespace Apis\Tests;

use Apis\Fetch;
use Apis\JSendException;
use Apis\FetchException;
use Openclerk\Config;

class FetchTest extends \PHPUnit_Framework_TestCase {

  function __construct() {
    Config::merge(array(
      "get_contents_timeout" => 10,
    ));
  }

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

  function test404() {
    try {
      Fetch::get("http://cryptfolio.com/404");
      $this->fail("Expected 404 to be thrown");
    } catch (FetchException $e) {
      $this->assertEquals("Remote server returned HTTP 404", $e->getMessage());
    }
  }

}
