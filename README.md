# Berry - A Refined Routing Library for Modern PHP Applications

[![Latest Version on Packagist](https://img.shields.io/packagist/v/ysocode/berry.svg?style=flat)](https://packagist.org/packages/ysocode/berry)
[![Downloads on Packagist](https://img.shields.io/packagist/dt/ysocode/berry.svg?style=flat)](https://packagist.org/packages/ysocode/berry)
[![License](https://img.shields.io/packagist/l/ysocode/berry)](https://packagist.org/packages/ysocode/berry)

## Introduction

Berry is a refined and strongly-typed routing library for PHP, designed with a deep focus on **domain integrity**, **immutability**, and **clarity**.

Rather than simply following the PSR standards (11, 15, and 17), Berry **reimagines and enhances** them, applying concepts such as **Value Objects**, **Enums**, and **Clean Architecture principles** to provide a more expressive and robust core.

Its design encourages composition over inheritance, enforcing consistency across request, response, URI, and middleware handling.  
The result is a minimal, elegant, and extensible router that keeps type safety and readability at the forefront.

## Official Documentation

### Install Berry using Composer:

```shell
composer require ysocode/berry
```

### Initial Configuration

To start your application, instantiate the Berry, register your routes, and invoke the `run()` method to process the incoming HTTP request:

```php
<?php

use DI\Container;
use YSOCode\Berry\Application\Berry;
use YSOCode\Berry\Domain\Enums\HttpStatus;
use YSOCode\Berry\Domain\ValueObjects\UriPath;
use YSOCode\Berry\Infra\Http\Response;
use YSOCode\Berry\Infra\Http\ServerRequest;
use YSOCode\Berry\Infra\Stream\StreamFactory;

require_once __DIR__.'/vendor/autoload.php';

$berry = new Berry(new Container);

$berry->get(new UriPath('/'), function (ServerRequest $request): Response {
    return new Response(
        HttpStatus::OK,
        body: new StreamFactory()->createFromString('Hello, world!')
    );
});

$berry->run();
```

#### Constructor Parameters

The `Berry` constructor accepts several parameters that allow full customization of its internal behavior and dependency management.

```php
public function __construct(
    ContainerInterface $container,
    Router $router = new Router,
    ?MiddlewareStackBuilder $middlewareStackBuilder = null,
    ?Dispatcher $dispatcher = null,
    ResponseEmitter $responseEmitter = new ResponseEmitter,
    array $middlewares = []
)
```

#### Parameters

| Parameter | Type | Description |
|------------|------|-------------|
| **`$container`** | `Psr\Container\ContainerInterface` | A PSR-11 compatible container (e.g. PHP-DI, League\Container). Used to automatically resolve route handlers and middlewares defined as class strings. |
| **`$router`** | `YSOCode\Berry\Application\Router` | *(Optional)* The router responsible for managing route definitions and lookups. If omitted, Berry creates a default router internally. |
| **`$middlewareStackBuilder`** | `YSOCode\Berry\Infra\Http\MiddlewareStackBuilder` | *(Optional)* Builds the middleware execution stack. If not provided, Berry creates a default instance using the same container. |
| **`$dispatcher`** | `YSOCode\Berry\Application\Dispatcher` | *(Optional)* Handles route dispatching and resolution. If omitted, a default dispatcher is created automatically. |
| **`$responseEmitter`** | `YSOCode\Berry\Infra\Http\ResponseEmitter` | *(Optional)* Sends the final response to the client. You can provide a custom emitter for specific environments (SAPI, CLI, or testing). |
| **`$middlewares`** | `array<class-string<MiddlewareInterface>\|Closure(ServerRequest $request, RequestHandlerInterface $handler): Response>` | *(Optional)* A list of global middlewares executed for every request. Each middleware can be a class name (resolved via container) or an inline closure. |

#### Global Middlewares

Berry comes with **no default middlewares**.  
By design, it is completely minimal and does not impose any middleware on your application.  

Berry allows you to attach middlewares that will run for all routes globally.

##### Adding a single global middleware:

```php
<?php

use DI\Container;
use YSOCode\Berry\Application\Berry;
use YSOCode\Berry\Infra\Http\Response;
use YSOCode\Berry\Infra\Http\RequestHandlerInterface;
use YSOCode\Berry\Infra\Http\ServerRequest;

require_once __DIR__.'/vendor/autoload.php';

$berry = new Berry(new Container);

$berry->addMiddleware(
    fn (ServerRequest $request, RequestHandlerInterface $handler): Response => $handler->handle($request)
);
```

##### Adding a multiple global middlewares:

```php
<?php

use DI\Container;
use YSOCode\Berry\Application\Berry;
use YSOCode\Berry\Infra\Http\Response;
use YSOCode\Berry\Infra\Http\RequestHandlerInterface;
use YSOCode\Berry\Infra\Http\ServerRequest;

require_once __DIR__.'/vendor/autoload.php';

$berry = new Berry(new Container);

$berry->addMiddlewares([
    fn (ServerRequest $request, RequestHandlerInterface $handler): Response => $handler->handle($request),
    fn (ServerRequest $request, RequestHandlerInterface $handler): Response => $handler->handle($request),
]);
```

### Routing Basics

Berry provides a clean and expressive API for defining HTTP routes.  
Each route maps a specific HTTP method and path to a handler responsible for producing a response.

The core routing methods available are:

- `get()`  
- `put()`  
- `post()`  
- `delete()`
- `patch()`  

Each method receives a `UriPath` value object and a route handler (usually a `Closure` or a class name).

```php
<?php

use YSOCode\Berry\Domain\Enums\HttpStatus;
use DI\Container;
use YSOCode\Berry\Application\Berry;
use YSOCode\Berry\Domain\ValueObjects\UriPath;
use YSOCode\Berry\Infra\Http\Response;
use YSOCode\Berry\Infra\Http\ServerRequest;
use YSOCode\Berry\Infra\Stream\StreamFactory;

require_once __DIR__.'/vendor/autoload.php';

$berry = new Berry(new Container);

$berry->get(new UriPath('/users'), function (ServerRequest $request): Response {
    return new Response(
        HttpStatus::OK,
        body: new StreamFactory()->createFromString('Listing all users.')
    );
});

$berry->put(new UriPath('/users/1'), function (ServerRequest $request): Response {
    return new Response(
        HttpStatus::OK,
        body: new StreamFactory()->createFromString('User #1 replaced successfully.')
    );
});

$berry->post(new UriPath('/users'), function (ServerRequest $request): Response {
    return new Response(
        HttpStatus::CREATED,
        body: new StreamFactory()->createFromString('User created successfully.')
    );
});

$berry->delete(new UriPath('/users/1'), function (ServerRequest $request): Response {
    return new Response(
        HttpStatus::NO_CONTENT
    );
});

$berry->patch(new UriPath('/users/1'), function (ServerRequest $request): Response {
    return new Response(
        HttpStatus::OK,
        body: new StreamFactory()->createFromString('User #1 updated successfully.')
    );
});
```

### Modifying Routes

Once a route is defined, Berry allows further configuration through the `Route` entity.  
This includes setting a name, adding middlewares, or modifying the path dynamically.

#### Setting a Route Name

The `setName()` method allows you to assign a unique name to a route, useful for route referencing or generating URLs.

```php
<?php

use YSOCode\Berry\Domain\Enums\HttpStatus;
use YSOCode\Berry\Domain\ValueObjects\UriPath;
use YSOCode\Berry\Infra\Http\Response;
use YSOCode\Berry\Infra\Http\ServerRequest;
use YSOCode\Berry\Infra\Stream\StreamFactory;
use YSOCode\Berry\Domain\ValueObjects\Name;

require_once __DIR__.'/vendor/autoload.php';

$berry = new Berry(new Container);

$berry->get(new UriPath('/'), function (ServerRequest $request): Response {
    return new Response(
        HttpStatus::OK,
        body: new StreamFactory()->createFromString('Hello, world!')
    );
})->setName(new Name('home'));
```

#### Adding Middlewares to a Route

Berry supports attaching middlewares specific to a single route.

##### Adding a single middleware:
```php
<?php

use YSOCode\Berry\Domain\Enums\HttpStatus;
use YSOCode\Berry\Domain\ValueObjects\UriPath;
use YSOCode\Berry\Infra\Http\Response;
use YSOCode\Berry\Infra\Http\RequestHandlerInterface;
use YSOCode\Berry\Infra\Http\ServerRequest;
use YSOCode\Berry\Infra\Stream\StreamFactory;

require_once __DIR__.'/vendor/autoload.php';

$berry = new Berry(new Container);

$berry->get(new UriPath('/'), function (ServerRequest $request): Response {
    return new Response(
        HttpStatus::OK,
        body: new StreamFactory()->createFromString('Hello, world!')
    );
})->addMiddleware(
    fn (ServerRequest $request, RequestHandlerInterface $handler): Response => $handler->handle($request)
);
```

##### Adding a multiple middlewares:
```php
<?php

use YSOCode\Berry\Domain\Enums\HttpStatus;
use YSOCode\Berry\Domain\ValueObjects\UriPath;
use YSOCode\Berry\Infra\Http\Response;
use YSOCode\Berry\Infra\Http\ServerRequest;
use YSOCode\Berry\Infra\Stream\StreamFactory;

require_once __DIR__.'/vendor/autoload.php';

$berry = new Berry(new Container);

$berry->get(new UriPath('/'), function (ServerRequest $request): Response {
    return new Response(
        HttpStatus::OK,
        body: new StreamFactory()->createFromString('Hello, world!')
    );
})->addMiddlewares([
    fn (ServerRequest $request, RequestHandlerInterface $handler): Response => $handler->handle($request),
    fn (ServerRequest $request, RequestHandlerInterface $handler): Response => $handler->handle($request),
]);
```

### Using Handlers and Middlewares as Classes

Berry allows you to define **handlers** and **middlewares** as standalone classes, ensuring type safety and consistent architecture.

#### Route Handlers as Classes

A route handler class must implement the `RequestHandlerInterface`:

```php
<?php

use YSOCode\Berry\Infra\Http\RequestHandlerInterface;
use YSOCode\Berry\Infra\Http\ServerRequest;
use YSOCode\Berry\Infra\Http\Response;
use YSOCode\Berry\Domain\Enums\HttpStatus;
use YSOCode\Berry\Infra\Stream\StreamFactory;

require_once __DIR__.'/vendor/autoload.php';

$berry = new Berry(new Container);

final class ProfileHandler implements RequestHandlerInterface
{
    public function handle(ServerRequest $request): Response
    {
        return new Response(
            HttpStatus::OK,
            body: new StreamFactory()->createFromString('User profile data.')
        );
    }
}
```

You can then register it to a route:

```php
$berry->get(new UriPath('/profile'), ProfileHandler::class);
```

#### Middlewares as Classes

A middleware class must implement the `MiddlewareInterface`:

```php
<?php

use YSOCode\Berry\Infra\Http\MiddlewareInterface;
use YSOCode\Berry\Infra\Http\RequestHandlerInterface;
use YSOCode\Berry\Infra\Http\ServerRequest;
use YSOCode\Berry\Infra\Http\Response;
use YSOCode\Berry\Domain\Enums\HttpStatus;
use YSOCode\Berry\Domain\ValueObjects\HeaderName;

require_once __DIR__.'/vendor/autoload.php';

$berry = new Berry(new Container);

final class AuthMiddleware implements MiddlewareInterface
{
    public function process(ServerRequest $request, RequestHandlerInterface $handler): Response
    {
        $authorizationHeader = $request->getHeader(new HeaderName('Authorization'));
        [$authorization] = $authorizationHeader->values ?? [null];
        if (! is_string($authorization)) {
            return new Response(HttpStatus::UNAUTHORIZED);
        }

        return $handler->handle($request);
    }
}
```

You can attach it globally:

```php
$berry->addMiddleware(AuthMiddleware::class);
```

Or attach it to a specific route:

```php
$berry->get(new UriPath('/profile'), ProfileHandler::class)
    ->addMiddleware(AuthMiddleware::class);
```

## License

Berry is open-sourced software licensed under the [MIT license](LICENSE).