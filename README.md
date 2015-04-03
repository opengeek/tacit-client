# Tacit Client

The Tacit Client project is a library for using RESTful APIs created with Tacit within a Slim web application.

## Installation

Use composer to require the `tacit\client` project.

```
user@host:~$ composer require "tacit/client dev-master"
```


## Usage

In your gateway PHP file for the application, add the Identities provider and API Client as singletons to the DI container:

```php
$app->container->singleton('identities', function() use ($app) {
    return (new \Tacit\Client\Identity($app->config('api.identities')));
});

$app->container->singleton('api', function() use ($app) {
    return (new \Tacit\Client($app, $app->config('api.endpoint')));
});
```

Then add the Session Middleware to the application:

```php
session_name($app->config('session.name'));
session_set_cookie_params(
    (integer)$app->config('session.lifetime'),
    rtrim($app->request->getRootUri(), '/') . '/'
);

$app->add(new \Tacit\Middleware\Session());
```
