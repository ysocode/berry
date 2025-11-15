# Berry - A Refined Routing Library for Modern PHP Applications

[![Latest Version on Packagist](https://img.shields.io/packagist/v/ysocode/berry.svg?style=flat)](https://packagist.org/packages/ysocode/berry)
[![Downloads on Packagist](https://img.shields.io/packagist/dt/ysocode/berry.svg?style=flat)](https://packagist.org/packages/ysocode/berry)
[![License](https://img.shields.io/packagist/l/ysocode/berry)](LICENSE)

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

### Overview

#### Apache Configuration

Ensure your `.htaccess` and `index.php` files are in the same public-accessible directory. The `.htaccess` file should contain this code:

```apacheconf
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [QSA,L]
```

To ensure that the `public/` directory does not appear in the URL, you should add a second `.htaccess` file above the `public/` directory with the following internal redirect rule:

```apacheconf
RewriteEngine on
RewriteRule ^$ public/ [L]
RewriteRule (.*) public/$1 [L]
```

#### Bootstrapping Berry with a PSR-11 Container

Berry requires a PSR-11 compatible container (such as PHP-DI) to handle dependencies.
Once the container is ready, you can instantiate Berry, register routes, and run the application.

```php
<?php

use DI\Container;
use YSOCode\Berry\Application\Berry;
use YSOCode\Berry\Infra\Http\Response;
use YSOCode\Berry\Infra\Http\ResponseFactory;
use YSOCode\Berry\Infra\Http\ServerRequest;

require_once __DIR__.'/vendor/autoload.php';

$berry = new Berry(new Container);

$berry->get('/', function (ServerRequest $request): Response {
    return new ResponseFactory()->fromBody('Hello, world!');
});

$berry->run();
```

#### Global Middlewares

Berry allows you to attach middlewares that will run for all routes globally.
All middlewares and handlers follow the PSR-7 HTTP message interface standard, ensuring full interoperability and compliance with modern PHP practices.

##### Adding a single global middleware:

```php
<?php

use DI\Container;
use YSOCode\Berry\Application\Berry;
use YSOCode\Berry\Infra\Http\RequestHandlerInterface;
use YSOCode\Berry\Infra\Http\Response;
use YSOCode\Berry\Infra\Http\ServerRequest;

require_once __DIR__.'/vendor/autoload.php';

$berry = new Berry(new Container);

$berry->addMiddleware(
    fn (ServerRequest $request, RequestHandlerInterface $handler): Response => $handler->handle($request)
);
```

##### Adding multiple global middlewares:

```php
<?php

use DI\Container;
use YSOCode\Berry\Application\Berry;
use YSOCode\Berry\Infra\Http\RequestHandlerInterface;
use YSOCode\Berry\Infra\Http\Response;
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
- `head()`
- `options()`

Each method receives a path and a route handler (usually a closure or a class name).
All handlers follow PSR-7 for HTTP messages.

```php
<?php

use DI\Container;
use YSOCode\Berry\Application\Berry;
use App\Handlers\User\ListUsersHandler;
use App\Handlers\User\UpdateUserHandler;
use App\Handlers\User\CreateUserHandler;
use App\Handlers\User\DeleteUserHandler;
use App\Handlers\User\PatchUserHandler;
use App\Handlers\User\UserHeadHandler;
use App\Handlers\User\UserOptionsHandler;

require_once __DIR__.'/vendor/autoload.php';

$berry = new Berry(new Container);

$berry->get('/users', ListUsersHandler::class);
$berry->put('/users/{id}', UpdateUserHandler::class);
$berry->post('/users', CreateUserHandler::class);
$berry->delete('/users/{id}', DeleteUserHandler::class);
$berry->patch('/users/{id}', PatchUserHandler::class);
$berry->head('/users/{id}', UserHeadHandler::class);
$berry->options('/users', UserOptionsHandler::class);

$berry->run();
```

### Modifying Routes

Once a route is defined, Berry allows further configuration through the `Route` entity.  
This includes setting a name, adding middlewares, or add a prefix.

#### Setting a Route Name

The `setName()` method allows you to assign a unique name to a route, useful for route referencing or generating URLs.

```php
<?php 

use DI\Container;
use YSOCode\Berry\Application\Berry;
use App\Handlers\HelloWorldHandler;

require_once __DIR__.'/vendor/autoload.php';

$berry = new Berry(new Container);

$berry->get('/', HelloWorldHandler::class)->setName('home');

$berry->run();
```

#### Adding Middlewares to a Route

Berry supports attaching middlewares specific to a single route.

##### Adding a single middleware:
```php
<?php 

use App\Handlers\HelloWorldHandler;
use DI\Container;
use YSOCode\Berry\Application\Berry;
use YSOCode\Berry\Infra\Http\RequestHandlerInterface;
use YSOCode\Berry\Infra\Http\Response;
use YSOCode\Berry\Infra\Http\ServerRequest;

require_once __DIR__.'/vendor/autoload.php';

$berry = new Berry(new Container);

$berry->get('/', HelloWorldHandler::class)
    ->addMiddleware(
        fn (ServerRequest $request, RequestHandlerInterface $handler): Response => $handler->handle($request)
    );

$berry->run();
```

##### Adding a multiple middlewares:
```php
<?php 

use App\Handlers\HelloWorldHandler;
use DI\Container;
use YSOCode\Berry\Application\Berry;
use YSOCode\Berry\Infra\Http\RequestHandlerInterface;
use YSOCode\Berry\Infra\Http\Response;
use YSOCode\Berry\Infra\Http\ServerRequest;

require_once __DIR__.'/vendor/autoload.php';

$berry = new Berry(new Container);

$berry->get('/', HelloWorldHandler::class)
    ->addMiddlewares([
        fn (ServerRequest $request, RequestHandlerInterface $handler): Response => $handler->handle($request),
        fn (ServerRequest $request, RequestHandlerInterface $handler): Response => $handler->handle($request),
    ]);

$berry->run();
```

### Route Groups

Berry allows grouping routes using `RouteGroup`, useful when you want to apply a prefix or shared middlewares to a set of routes.
You configure the group via a closure passed to `$berry->group(...)`.
Before `run()` all groups are propagated automatically and their routes are registered in the application.

Example usage:

```php
<?php

use DI\Container;
use YSOCode\Berry\Application\Berry;
use YSOCode\Berry\Domain\Entities\RouteGroup;
use App\Handlers\User\ListUsersHandler;
use App\Handlers\User\CreateUserHandler;
use YSOCode\Berry\Infra\Http\RequestHandlerInterface;
use YSOCode\Berry\Infra\Http\Response;
use YSOCode\Berry\Infra\Http\ServerRequest;

require_once __DIR__.'/vendor/autoload.php';

$berry = new Berry(new Container);

$berry->group(function (RouteGroup $group): void {
        $group->get('/users', ListUsersHandler::class);
        $group->post('/users', CreateUserHandler::class);
})
    ->addPrefix('/api/v1')
    ->addMiddlewares([
        fn (ServerRequest $request, RequestHandlerInterface $handler): Response => $handler->handle($request),
        fn (ServerRequest $request, RequestHandlerInterface $handler): Response => $handler->handle($request),
    ]);

$berry->run();
```

Important behavior:
- The `prefix` defined on a `RouteGroup` is propagated to each route when groups are processed before route resolution.
- Middlewares added to the group are attached to the group's routes during propagation.
- Use the `get`, `post`, `put`, etc. methods directly on the `$group` (provided by the `RouteRegistryProxyTrait`).

### ServerRequest and Response

Berry provides its own implementations of `ServerRequest` and `Response`, inspired by PSR-7 but improved for clarity and stronger typing.

#### ServerRequest

Represents the HTTP request received by the server. Automatically populates data from `$_SERVER`, `$_GET`, `$_POST`, `$_COOKIE`, `$_FILES`, and the request body.

**Key properties:**

| Property | Description |
|----------|-------------|
| `method` | HTTP method (`GET`, `POST`, etc.) |
| `uri` | `Uri` object with scheme, host, path, and query |
| `target` | Request target string (path + query) |
| `headers` | Collection of `Header` objects |
| `body` | Request body as a `Stream` |
| `serverParams` | Data from `$_SERVER` |
| `cookieParams` | Data from `$_COOKIE` |
| `queryParams` | Data from `$_GET` |
| `parsedBody` | Data from `$_POST` |
| `uploadedFiles` | Uploaded files (`UploadedFile`) |
| `attributes` | Custom attributes that can be added dynamically |

**Key methods (immutable):**

| Method | Returns | Description |
|--------|---------|------------|
| `withMethod(HttpMethod $method)` | `self` | Change HTTP method |
| `withUri(string $uri)` | `self` | Change URI |
| `withTarget(string $target)` | `self` | Change request target |
| `hasHeader(string $name)` | `bool` | Check if a header exists |
| `getHeader(string $name)` | `?Header` | Get a specific header |
| `withHeader(string $name, array $values)` | `self` | Set a header (overwrites if exists) |
| `withAddedHeader(string $name, array $values)` | `self` | Add values to an existing header |
| `withoutHeader(string $name)` | `self` | Remove a header |
| `withBody(string $body)` | `self` | Change the request body |
| `withVersion(HttpVersion $version)` | `self` | Change HTTP version |
| `withCookieParams(array $cookieParams)` | `self` | Replace cookies |
| `withQueryParams(array $queryParams)` | `self` | Replace query parameters |
| `withParsedBody(array $parsedBody)` | `self` | Replace parsed body |
| `withUploadedFiles(array $uploadedFiles)` | `self` | Replace uploaded files |
| `hasAttribute(string $name)` | `bool` | Check if a custom attribute exists |
| `getAttribute(string $name)` | `?Attribute` | Get a custom attribute |
| `withAttribute(string $name, mixed $value)` | `self` | Add a custom attribute |
| `withoutAttribute(string $name)` | `self` | Remove a custom attribute |

> Note: Methods like `withHeader`, `withAddedHeader`, `withoutHeader`, `withBody`, and `withVersion` also exist in the `Response` class (`MessageTrait`), ensuring a consistent API for both requests and responses.

---

#### Response

Represents the HTTP response sent to the client. Maintains **immutability** and simplicity.

**Key properties:**

| Property | Description |
|----------|-------------|
| `status` | HTTP status (`HttpStatus`) |
| `headers` | Collection of `Header` objects |
| `body` | Response body as a `Stream` |

**Key methods (immutable):**

| Method | Returns | Description |
|--------|---------|------------|
| `withStatus(HttpStatus $status)` | `self` | Change HTTP status code |
| `withHeader(string $name, array $values)` | `self` | Set a header (overwrites if exists) |
| `withAddedHeader(string $name, array $values)` | `self` | Add values to an existing header |
| `withoutHeader(string $name)` | `self` | Remove a header |
| `withBody(string $body)` | `self` | Change response body |
| `withVersion(HttpVersion $version)` | `self` | Change HTTP version |

> These methods mirror `ServerRequest` methods, providing a unified interface for HTTP message manipulation.

---

#### ResponseFactory

Provides a simple way to create a `Response` from a string body.

**Example usage in a handler:**

```php
<?php

namespace App\Handlers;

use YSOCode\Berry\Infra\Http\RequestHandlerInterface;
use YSOCode\Berry\Infra\Http\Response;
use YSOCode\Berry\Infra\Http\ResponseFactory;
use YSOCode\Berry\Infra\Http\ServerRequest;

final readonly class HelloWorldHandler implements RequestHandlerInterface
{
    public function handle(ServerRequest $request): Response
    {
        return new ResponseFactory()->fromBody('Hello, world!');
    }
}
```

### Route Context and RouteParser

During request processing Berry attaches a `RouteContext` to the `ServerRequest` via the `RouteContextMiddleware`.
The recommended way to obtain a validated `RouteContext` inside a handler or middleware is to use `RouteContextFactory::createFromRequest(ServerRequest $request)`.

What `RouteContext` provides:
- `route`: the matched `Route` instance.
- `routeParser`: a `RouteParser` instance that can be used to inspect routes or build paths.
- `basePath`: optional `UriPath` if the application has a base path set.

`RouteParametersMiddleware` also injects each route parameter into the request as an attribute
so you can access parameters directly with `$request->getAttribute('{name}')`.

Useful `RouteParser` methods:
- `hasRouteByName(string $name): bool` — returns whether a named route exists.
- `getRouteByName(string $name): ?Route` — returns the `Route` for a name (or `null`).
- `resolvePathForRouteByName(string $name, array $parameters = [], bool $withBasePath = true): ?UriPath` — builds a `UriPath` for a named route using parameters.

```php
<?php

namespace App\Handlers;

use YSOCode\Berry\Domain\Entities\RouteContextFactory;
use YSOCode\Berry\Domain\Enums\HttpStatus;
use YSOCode\Berry\Infra\Http\RequestHandlerInterface;
use YSOCode\Berry\Infra\Http\Response;
use YSOCode\Berry\Infra\Http\ServerRequest;

final readonly class HelloWorldHandler implements RequestHandlerInterface
{
    public function handle(ServerRequest $request): Response
    {
        $routeContext = new RouteContextFactory()->createFromRequest($request);

        $routeParser = $routeContext->routeParser;
        $path = $routeParser->resolvePathForRouteByName('user.show', ['id' => '42']);

        return new Response(HttpStatus::MOVED_PERMANENTLY)
            ->withHeader('Location', [(string) $path]);
    }
}
```

Notes:
- `RouteContextFactory::createFromRequest(ServerRequest $request)` validates attributes and throws a `RuntimeException` if the expected attributes are missing or invalid.- 
- If you only need route parameters, they are available as request attributes (injected by `RouteParametersMiddleware`).

## License

Berry is open-sourced software licensed under the [MIT license](LICENSE).