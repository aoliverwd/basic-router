![PHPUnit](https://github.com/aoliverwd/basic-router/actions/workflows/ci.yml/badge.svg) [![Latest Stable Version](https://poser.pugx.org/alexoliverwd/basic-router/v)](//packagist.org/packages/alexoliverwd/basic-router) [![License](https://poser.pugx.org/alexoliverwd/basic-router/license)](//packagist.org/packages/alexoliverwd/basic-router)

# Basic Router

This application is a minimalistic yet powerful PHP class designed to handle routing in web applications. It's a dependency-free solution that offers a straightforward approach to mapping HTTP requests to specific PHP functions or methods.

## Installation

Preferred installation is via Composer:

```bash
composer require alexoliverwd/basic-router
```

## Basic Usage

When a request comes in to the application, the Router instance will examine the request method (GET, POST, PUT, DELETE) and the requested URL. If a matching route is found, the associated callback function will be executed.
### Attribute-Based Controller Routing

Attributes provide a modern, native way to declare route metadata directly in your code. This approach is now recommended for defining routes, while conventional methods will remain supported for compatibility.

```php
use AOWD\Router;
use AOWD\Attributes\Route;

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
}

$router = new Router();
$router->registerRouteController(new myRoutes());
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

$router->run();
```

In this example, if a GET request is made to the root URL (/), the function function () { echo 'get'; } will be called, and the string "get" will be output.

### Registering an endpoint

The ```register``` method registers a new route in the routing system.
#### Parameters

1. Method: The HTTP method (e.g., GET, POST, PUT, DELETE).
2. Route: The URL pattern for the route.
3. Callback: The callable function or method to be executed when the route is matched.

#### Return Value:

* true: If the route is successfully registered.
* false: If the route already exists for the specified method.


### Executing registered routes

The ```run()``` method is the core of the routing system. It's responsible for:

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

1. Method: The HTTP method (e.g., GET, POST, PUT, DELETE) in lowercase.
2. Route: The URL pattern of the route to be unregistered.

#### Return Value:

* true: If the route is successfully unregistered.
* false: If the route doesn't exist or couldn't be unregistered.


### Handling 404 errors

```php
$router->register404(function () {
    echo '404 error';
});
```

The ```register404``` method registers a callback function to be executed when a 404 Not Found error occurs. This allows you to customize the error handling behavior for your application.

#### Parameters

1. Callback: A callable function or method that will be invoked when a 404 error is encountered. This callback can be used to generate custom error messages, redirect to a specific page, or perform other error handling actions.