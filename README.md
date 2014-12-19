openclerk/apis
==============

A library for defining APIs in Openclerk.

## Installing

Include `openclerk/apis` as a requirement in your project `composer.json`,
and run `composer update` to install it into your project:

```json
{
  "require": {
    "openclerk/apis": "dev-master"
  },
  "repositories": [{
    "type": "vcs",
    "url": "https://github.com/openclerk/apis"
  }]
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

## TODO

1. API caching support
1. Tests
1. Publish on Packagist
