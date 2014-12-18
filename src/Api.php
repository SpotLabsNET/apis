<?php

namespace Apis;

abstract class Api {

  abstract function getJSON($arguments);

  abstract function getEndpoint();

  // TODO caching

}
