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

### Web Servers

#### Apache Configurations

Ensure your `.htaccess` and `index.php` files are in the same public-accessible directory. The `.htaccess` file should contain this code:

```apacheconf
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [QSA,L]
```

To ensure that the `public/` directory does not appear in the URL, you should add a second `.htaccess` file above the `public/` directory with the following internal redirect rule:

```apacheconf
RewriteEngine On
RewriteRule ^$ public/ [L]
RewriteRule (.*) public/$1 [L]
```

### Installation

#### Install Berry:

We recommend installing Berry via Composer. Navigate to your project’s root directory and run the following command:

```shell
composer require ysocode/berry
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
    return new ResponseFactory()->createFromString('Hello, world!');
});

$berry->run();
```

### Global Configurations

#### Adding Global Middlewares

Middlewares are inspired by PSR-15 concepts and work with request and response objects based on PSR-7, with refinements
for stronger typing and usability.

**Adding a single global middleware:**

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

**Adding multiple global middlewares:**

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
Handlers are inspired by PSR-15 concepts and work with request and response objects based on PSR-7, with refinements
for stronger typing and usability.

```php
<?php

use DI\Container;
use App\Handlers\User\CreateUserHandler;
use App\Handlers\User\DeleteUserHandler;
use App\Handlers\User\ListUsersHandler;
use App\Handlers\User\PatchUserHandler;
use App\Handlers\User\UpdateUserHandler;
use App\Handlers\User\UserHeadHandler;
use App\Handlers\User\UserOptionsHandler;
use YSOCode\Berry\Application\Berry;

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

use App\Handlers\HelloWorldHandler;
use DI\Container;
use YSOCode\Berry\Application\Berry;

require_once __DIR__.'/vendor/autoload.php';

$berry = new Berry(new Container);

$berry->get('/', HelloWorldHandler::class)->setName('home');

$berry->run();
```

#### Adding Middlewares to a Route

Berry supports attaching middlewares specific to a single route.

**Adding a single middleware:**

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

**Adding a multiple middlewares:**

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
You configure the group via a closure passed to `Berry::group(RouteGroup)`.
Before `run()` all groups are propagated automatically and their routes are registered in the application.

**Example usage:**

```php
<?php

use DI\Container;
use App\Handlers\User\CreateUserHandler;
use App\Handlers\User\ListUsersHandler;
use YSOCode\Berry\Application\Berry;
use YSOCode\Berry\Domain\Entities\RouteGroup;
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

- The `prefix` set on a `RouteGroup` is applied to each route when the group is propagated.
- Middlewares added to a `RouteGroup` are attached to all of its routes during propagation.
- Use the HTTP methods (`get`, `post`, `put`, etc.) directly on the `$group` object (provided by the `RouteRegistryProxyTrait`).

### ServerRequest and Response

Berry provides its own implementations of `ServerRequest` and `Response`, inspired by PSR-7 but improved for clarity and stronger typing.

#### ServerRequest

Represents an immutable HTTP request received by the server.  
It is automatically populated from PHP superglobals and structured using strongly-typed value objects for method, URI, headers, body, parameters, and attributes.

**Key properties:**

| Property        | Description |
|-----------------|-------------|
| `method`        | HTTP method of the request |
| `uri`           | The request URI |
| `target`        | Full request target (path + query string) |
| `headers`       | Normalized header collection |
| `body`          | Stream representing the request body |
| `serverParams`  | Server environment parameters (`$_SERVER`) |
| `cookieParams`  | Cookies sent by the client (`$_COOKIE`) |
| `queryParams`   | Query string parameters (`$_GET`) |
| `parsedBody`    | Parsed body data (`$_POST`) |
| `uploadedFiles` | Files uploaded with the request (`$_FILES`) |
| `attributes`    | Arbitrary user-defined attributes |

**Key methods (immutable):**

| Method | Returns | Description |
|--------|---------|-------------|
| `withMethod(HttpMethod $method)` | `self` | Change the HTTP method |
| `withUri(string $uri)` | `self` | Change the URI |
| `withTarget(string $target)` | `self` | Change the request target |
| `hasHeader(string $name)` | `bool` | Determine whether a header exists |
| `getHeader(string $name)` | `?Header` | Retrieve a specific header |
| `withHeader(string $name, array $values)` | `self` | Set a header (replaces existing value) |
| `withAddedHeader(string $name, array $values)` | `self` | Add values to an existing header |
| `withoutHeader(string $name)` | `self` | Remove a header |
| `withBody(string $body)` | `self` | Replace the request body |
| `withVersion(HttpVersion $version)` | `self` | Change the HTTP protocol version |
| `withCookieParams(array $cookieParams)` | `self` | Replace cookie parameters |
| `withQueryParams(array $queryParams)` | `self` | Replace query parameters |
| `withParsedBody(array $parsedBody)` | `self` | Replace parsed body data |
| `withUploadedFiles(array $uploadedFiles)` | `self` | Replace uploaded files |
| `hasAttribute(string $name)` | `bool` | Determine whether an attribute exists |
| `getAttribute(string $name)` | `?Attribute` | Retrieve an attribute |
| `withAttribute(string $name, mixed $value)` | `self` | Add a new attribute |
| `withoutAttribute(string $name)` | `self` | Remove an attribute |

> Methods involving headers, body, and HTTP version come from `MessageTrait`, which is shared with the `Response` class.

#### Response

Represents the HTTP response sent back to the client.  
Like all core Berry objects, it is fully immutable.

**Key properties:**

| Property | Description |
|----------|-------------|
| `status` | Response status |
| `headers` | Normalized header collection |
| `body` | Stream containing the response body |

**Key methods (immutable):**

| Method | Returns | Description |
|--------|---------|-------------|
| `withStatus(HttpStatus $status)` | `self` | Change the response status |
| `withHeader(string $name, array $values)` | `self` | Set a header (replaces existing value) |
| `withAddedHeader(string $name, array $values)` | `self` | Add values to an existing header |
| `withoutHeader(string $name)` | `self` | Remove a header |
| `withBody(string $body)` | `self` | Replace the response body |
| `withVersion(HttpVersion $version)` | `self` | Change the HTTP protocol version |

> These methods are provided by the shared `MessageTrait`, ensuring a consistent API across `ServerRequest` and `Response`.

#### ResponseFactory

Provides a simple way to create a `Response` from a string.

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
        return new ResponseFactory()->createFromString('Hello, world!');
    }
}
```

### Route Context and RouteParser

During request processing Berry attaches a `RouteContext` to the `ServerRequest` via the `RouteContextMiddleware`.
The recommended way to obtain a validated `RouteContext` inside a handler or middleware is to use `RouteContextFactory::createFromRequest(ServerRequest)`.

What `RouteContext` provides:

- `route`: the matched `Route` instance.
- `routeParser`: a `RouteParser` instance that can be used to inspect routes or build paths.
- `basePath`: optional `UriPath` if the application has a base path set.

`RouteParametersMiddleware` also injects each route parameter into the request as an attribute
so you can access parameters directly with `ServerRequest::getAttribute(string)`.

Useful `RouteParser` methods:

- `hasRouteByName(string): bool` — returns whether a named route exists.
- `getRouteByName(string): ?Route` — returns the `Route` for a name (or `null`).
- `resolvePathForRouteByName(string, array, bool): ?UriPath` — builds a `UriPath` for a named route using parameters.

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

- `RouteContextFactory::createFromRequest(ServerRequest)` validates attributes and throws a `RuntimeException` if the expected
attributes are missing or invalid.
- If you only need route parameters, they are available as request attributes (injected by `RouteParametersMiddleware`).

### Customizing Error Handlers (via Container)

Berry allows customizing how three categories of errors are handled. This is done by providing implementations
for the following interfaces:

- `MethodNotAllowedHandlerInterface` (used when the error is "Method not allowed")
- `NotFoundHandlerInterface` (used when the error is "Not found")
- `InternalServerErrorHandlerInterface` (fallback for all other errors)

By binding your own implementations to these interfaces in the container, you can fully replace the default behavior for each error type.
If an interface is not bound in the container, the factory will use the appropriate default handler class.

```php
<?php

use DI\ContainerBuilder;
use YSOCode\Berry\Application\Berry;
use App\Handlers\CustomInternalServerErrorHandler;
use App\Handlers\CustomMethodNotAllowedHandler;
use App\Handlers\CustomNotFoundHandler;
use YSOCode\Berry\Infra\Http\InternalServerErrorHandlerInterface;
use YSOCode\Berry\Infra\Http\MethodNotAllowedHandlerInterface;
use YSOCode\Berry\Infra\Http\NotFoundHandlerInterface;

$builder = new ContainerBuilder();
$builder->addDefinitions([
    NotFoundHandlerInterface::class => CustomNotFoundHandler::class,
    MethodNotAllowedHandlerInterface::class => CustomMethodNotAllowedHandler::class,
    InternalServerErrorHandlerInterface::class => CustomInternalServerErrorHandler::class,
]);

$container = $builder->build();

$berry = new Berry($container);

$berry->run();
```

## License

Berry is open-sourced software licensed under the [MIT license](LICENSE).