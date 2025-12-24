![PHPUnit](https://github.com/aoliverwd/basic-router/actions/workflows/ci.yml/badge.svg) [![Latest Stable Version](https://poser.pugx.org/alexoliverwd/basic-router/v)](//packagist.org/packages/alexoliverwd/basic-router) [![License](https://poser.pugx.org/alexoliverwd/basic-router/license)](//packagist.org/packages/alexoliverwd/basic-router)

# Router

This application is a minimalistic yet powerful PHP class designed to handle routing in web applications. It's a dependency-free solution that offers a straightforward approach to mapping HTTP requests to functions or methods, with full support for registering routes and middleware using PHP Attributes.

> [!IMPORTANT]
> To prevent XSS attacks, use the built-in `getParameter` method when retrieving values from query parameters. 
> 
> For more information on using the `getParameter` method, see the [XSS Prevention](#xss-prevention) section.
## Installation

Preferred installation is via Composer:

```bash
composer require alexoliverwd/basic-router
```

## Usage

When a request comes in to the application, the Router instance will examine the request method (GET, POST, PUT, DELETE) and the requested URL. If a matching route is found, the associated callback function will be executed.

### Attribute-Based Controller Routing

Attributes provide a modern, native way to declare route metadata directly in your code. This approach is now recommended for defining routes, while conventional methods will remain supported for compatibility.

```php
use AOWD\Router;
use AOWD\Attributes\Route;
use AOWD\Attributes\GET;
use AOWD\Attributes\PUT;
use AOWD\Attributes\POST;
use AOWD\Attributes\DELETE;

class myRoutes {
    #[Route('/hello-world', 'get')]
    public function homeGet(): void
    {
        echo "GET - Hello World";
    }

    #[Route('/hello-world/segment/[0-9]+', 'get')]
    public function homeGetSegment(Router $router): void
    {
        echo $router->getSegment(1);
    }

    #[Route('/hello-world', 'post')]
    public function homePost(): void
    {
        echo "POST - Hello World";
    }

    #[Route('/users/{userId}/orders/{orderId}', 'get')]
    public function getUserOrder(Router $router): void
    {
        $user_id = $router->URLAttribute("userId");
        $order_id = $router->URLAttribute("orderId");
        echo "User: $user_id Order: $order_id";
    }
    
    // CRUD Attributes

    #[GET('/crud-hello-world')]
    public function crudHomeGet(): void
    {
        echo "CRUD GET - Hello World";
    }

    #[PUT('/crud-hello-world')]
    public function crudHomePut(): void
    {
        echo "CRUD PUT - Hello World";
    }

    #[POST('/crud-hello-world')]
    public function crudHomePost(): void
    {
        echo "CRUD POST - Hello World";
    }

    #[DELETE('/crud-hello-world')]
    public function crudHomeDelete(): void
    {
        echo "CRUD DELETE - Hello World";
    }
}

$router = new Router();
$router->registerRouteController(new myRoutes());
$router->run();
```

### Route Interface

Classes that implement the route interface have access to the ```prepend_path``` variable. This variable allows all routes registered within the class to be automatically prefixed with a common path.

This is useful when all routes in a class must follow a shared convention, such as starting with ```/api/```. If the convention needs to change in the future, it can be updated in one place at the top level rather than modifying every individual route definition within the class.

```php
use AOWD\Interfaces\Route as RouteInterface;
use AOWD\Attributes\Route;
use AOWD\Attributes\GET;
use AOWD\Router;

class routeWithPrependedSlug implements RouteInterface
{
    public string $prepend_path;
    public function __construct() {
        $this->prepend_path = $_ENV['PAGE_WITH_SLUG'];
    }

    #[Route('-env', 'get')]
    public function pageWithEnv(): void
    {
        echo "Page with ENV";
    }

    #[GET('-crud-hello-world')]
    public function homeCrudGET(): void
    {
        echo "CRUD GET - Hello World";
    }
}

$router = new Router();
$router->registerRouteController(new routeWithPrependedSlug());
$router->run();
```

### Registering Multiple Controllers

In the below example, multiple controllers can be registered with the router in a single call using the `registerRouteController()` method. Each controller class defines its own set of routes through PHP attributes, the router will automatically scan and map them.

```php
use AOWD\Router;
use AOWD\Attributes\Route;

class getRoutes {
    #[Route('/hello-world', 'get')]
    public function homeGet(): void
    {
        echo "GET - Hello World";
    }

    #[Route('/hello-world/segment/[0-9]+', 'get')]
    public function homeGetSegment(Router $router): void
    {
        echo $router->getSegment(1);
    }
}

class postRoutes {
    #[Route('/hello-world', 'post')]
    public function homePost(): void
    {
        echo "POST - Hello World";
    }
}

$router = new Router();
$router->registerRouteController(new getRoutes(), new postRoutes());
$router->run();
```

### Conventional Method

```php
use AOWD\Router;

$router = new Router();

$router->register('GET', '/', function () {
    echo 'get';
});

$router->register("get", "/second/segment/[0-9]+", function () use ($router) {
    echo $router->getSegment(1);
});

// CRUD Helper Methods

$router->get('/', function () {
    echo 'get';
});

$router->put('/', function () {
    echo 'put';
});

$router->post('/', function () {
    echo 'post';
});

$router->delete('/', function () {
    echo 'delete';
});

$router->run();
```

In this example, if a GET request is made to the root URL (/), the function function () { echo 'get'; } will be called, and the string "get" will be output.

### Registering an endpoint

The `register` method registers a new route in the routing system.

#### Parameters

1. **Method:** The HTTP method (e.g., GET, POST, PUT, DELETE).
2. **Route:** The URL pattern for the route.
3. **Callback:** The callable function or method to be executed when the route is matched.

#### Return Value:

- true: If the route is successfully registered.
- false: If the route already exists for the specified method.

### Executing registered routes

The `run()` method is the core of the routing system. It's responsible for:

1. Parsing the Request: Extracts the requested URL path and HTTP method from the server environment.
2. Matching Routes: Compares the parsed request against registered routes to find a matching route.
3. Executing Callback: If a match is found, the associated callback function is executed.
4. Handling 404 Errors: If no match is found, the registered 404 error handler is invoked.

### Unregistering an endpoint

```php
$router->unregister('get', '/get');
```

This method unregisters a previously registered route from the routing system.

#### Parameters

1. **Method:** The HTTP method (e.g., GET, POST, PUT, DELETE) in lowercase.
2. **Route:** The URL pattern of the route to be unregistered.

#### Return Value:

- true: If the route is successfully unregistered.
- false: If the route doesn't exist or couldn't be unregistered.

### Handling 404 errors

```php
$router->register404(function () {
    echo '404 error';
});
```

The `register404` method registers a callback function to be executed when a 404 Not Found error occurs. This allows you to customize the error handling behavior for your application.

#### Parameters

1. **Callback:** A callable function or method that will be invoked when a 404 error is encountered. This callback can be used to generate custom error messages, redirect to a specific page, or perform other error handling actions.

### Returning values from URL segments

The `getSegment` method returns a single segment value from a URI. The returned value may be an integer or a string, depending on how the segment is referenced. The method accepts either an integer index or a string key.
#### Using an integer index

When an integer is provided, it represents the position of the segment in the URI, counted from left to right, starting at `0`.

For example, given the URI: `segment/standard/123`
- `getSegment(0)` returns `segment`
- `getSegment(1)` returns `standard`
- `getSegment(2)` returns `123`

Negative integers are also supported and count segments from right to left:
- `getSegment(-1)` returns `123`
- `getSegment(-2)` returns `standard`
#### Using a string key

When a string is provided, `getSegment` returns the value associated with a URI template attribute.

For example, given the URI template: `segment/standard/{id}`
Calling: `getSegment('id')`
returns: `123`

For more information about URI template attributes, see the next section.
### Returning values from URL attributes

When defining a route that includes attribute placeholders (e.g.,
`/users/{userId}/orders/{orderId}`), you can easily retrieve those values by using the URLAttribute method.

```php
#[Route('/users/{userId}/orders/{orderId}', 'get')]
public function URLAttributesTest(Router $router): void
{
    $user_id = $router->URLAttribute("userId");
    $order_id = $router->URLAttribute("orderId");
    echo "User: $user_id Order: $order_id";
}
```

#### Parameters

1. **Reference ID _(string)_** — The name of the URL attribute to retrieve (e.g., `userId`).
2. **Fallback _(string|int, optional)_** — A value to return if the requested attribute is not found.

> [!NOTE]
> Attribute names may only contain alphabetic characters (A–Z, a–z), hyphens, and underscores (i.e., a-z, A-Z, -, \_).

---
# XSS Prevention

## Get safe values from query string parameters

To mitigate the risk of cross-site scripting (XSS) attacks, use the built-in `getParameter` method. This method retrieves a sanitised scalar value from a query string parameter, helping prevent the injection of malicious scripts.

```php
use AOWD\Router;

$router = new Router();

// URL: localhost://xss?q=<script>alert('XSS')</script>
$router->register("get", "/xss", function () use ($router) {
	// Will display &lt;script&gt;alert(&#039;XSS&#039;)&lt;/script&gt;
    echo $router->getParameter('q');
});

$router->run();
```

---
# Middleware

Middleware in this routing API provides a way to intercept and process requests before they reach your route handler. This allows you to implement reusable logic such as **authentication, logging, CORS handling, rate limiting, or response modification** without duplicating code inside your route controllers.

Middleware classes must implement or extend the `AOWD\Interfaces\Middleware` interface, which requires a `handle()` method. When a route is matched, any attached middleware will be executed in the order they are defined.
## Usage

### 1. Creating a Middleware

To create a middleware, implement the `MiddlewareInterface` and define the `handle()` method.

```php
use AOWD\Interfaces\Middleware as MiddlewareInterface;

class helloWorld implements MiddlewareInterface
{
    public function handle(): void
    {
        echo "Hello World ";
    }
}
```

This simple example outputs `Hello World` before the route logic executes.

### 2. Attaching Middleware to a Route

Middleware can be attached to a route using the `#[Middleware()]` attribute.

```php
use AOWD\Attributes\Route;
use AOWD\Attributes\Middleware;

class myRoutes {
    #[Route('/hello-world-middleware', 'get')]
    #[Middleware(helloWorld::class)]
    public function homeGetMiddleware(): void
    {
        echo "GET";
    }
}
```

In this example:

- A `GET` request to `/hello-world-middleware` will first run the `helloWorld` middleware.
- The middleware prints `"Hello World "`.
- Then the route handler executes and prints `"GET"`.
- The final response is:

```txt
Hello World GET
```

### 3. Registering Routes and Running the Router

After defining routes and middleware, register your route controller with the `Router` and start it:

```php
use AOWD\Router;

$router = new Router();
$router->registerRouteController(new myRoutes());
$router->run();
```

## Full Example

Here’s everything combined into a single working example:

```php
use AOWD\Router;
use AOWD\Attributes\Route;
use AOWD\Attributes\Middleware;
use AOWD\Interfaces\Middleware as MiddlewareInterface;

class helloWorld implements MiddlewareInterface
{
    public function handle(): void
    {
        echo "Hello World ";
    }
}

class myRoutes {
    #[Route('/hello-world-middleware', 'get')]
    #[Middleware(helloWorld::class)]
    public function homeGetMiddleware(): void
    {
        echo "GET";
    }
}

$router = new Router();
$router->registerRouteController(new myRoutes());
$router->run();
```
