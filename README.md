openclerk/apis [![Build Status](https://travis-ci.org/openclerk/apis.svg)](https://travis-ci.org/openclerk/apis)
==============

A library for defining APIs in Openclerk, live on [CryptFolio](https://cryptfolio.com/api).

## Installing

Include `openclerk/apis` as a requirement in your project `composer.json`,
and run `composer update` to install it into your project:

```json
{
  "require": {
    "openclerk/apis": "dev-master"
  }
}
```

## Using

Define subclasses of `\Apis\Api` to define endpoints and content:

```php
/**
 * API to get a single currency properties.
 */
class Currency extends \Apis\Api {

  function getJSON($arguments) {
    $cur = \DiscoveredComponents\Currencies::getInstance($arguments['currency']);
    $result = array(
      'code' => $cur->getCode(),
      'title' => $cur->getName(),
    );

    return $result;
  }

  function getEndpoint() {
    return "/api/v1/currency/:currency";
  }

}
```

You can then call `$api->render()` for the specific API.

## Using with magic

Your APIs can be discovered with [component-discovery](https://github.com/soundasleep/component-discovery)
by defining `apis.json`:

```json
{
  "api/v1/currencies": "\\Core\\Api\\Currencies",
  "api/v1/currency/:currency": "\\Core\\Api\\Currency"
}
```

You can then load these into [openclerk/routing](https://github.com/openclerk/routing) at runtime:

```php
// load up API routes
foreach (DiscoveredComponents\Apis::getAllInstances() as $uri => $handler) {
  \Openclerk\Router::addRoutes(array(
    $uri => $handler,
  ));
}
```

## Caching

Using [openclerk/cache](https://github.com/openclerk/cache) you can also cache API calls:

```php
/**
 * API to get a single currency properties.
 */
class Currency extends \Apis\CachedApi {

  // ...

  function getHash($arguments) {
    return substr($arguments['currency'], 0, 32);
  }

  function getAge() {
    return 60; /* cache age in seconds */
  }
}
```

## TODO

1. Documentation on Apis\Fetch methods
1. A way to define APIs lazily without instantiating all Apis at every request time
1. Tests
