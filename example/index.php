<?php

namespace AOWD\Example;

use AOWD\Router;
use AOWD\Attributes\Route;
use AOWD\Attributes\GET;
use AOWD\Attributes\PUT;
use AOWD\Attributes\POST;
use AOWD\Attributes\DELETE;
use AOWD\Attributes\Middleware;
use AOWD\Interfaces\Route as RouteInterface;
use AOWD\Interfaces\Middleware as MiddlewareInterface;

include_once dirname(__DIR__) . "/vendor/autoload.php";

$_ENV['PAGE_WITH_SLUG'] = "/page-with";

class theBest implements MiddlewareInterface
{
    public function handle(): void
    {
        echo "The best ";
    }
}

class helloWorld implements MiddlewareInterface
{
    public function handle(): void
    {
        echo "Hello World ";
    }
}

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
}

class getRoutes {
    #[Route('/hello-world-middleware', 'get')]
    #[Middleware(helloWorld::class)]
    public function homeGetMiddleware(): void
    {
        echo "GET";
    }

    #[Route('/hello-world-multi-middleware', 'get')]
    #[Middleware(theBest::class)]
    #[Middleware(helloWorld::class)]
    public function homeGetMultiMiddleware(): void
    {
        echo "GET";
    }

    #[Route('/hello-world', 'get')]
    public function homeGet(): void
    {
        echo "GET - Hello World";
    }

    #[Route('/hello-world/segment/[0-9]*', 'get')]
    public function homeGetSegment(Router $router): void
    {
        echo $router->getSegment(1);
    }

    #[Route('/users/{userId}/orders/{orderId}', 'get')]
    public function URLAttributesTest(Router $router): void
    {
        $user_id = $router->URLAttribute("userId");
        $order_id = $router->URLAttribute("orderId");
        echo "User: $user_id Order: $order_id";
    }

    #[Route('/users/{user_Id}', 'get')]
    public function URLAttributeFallbackTest(Router $router): void
    {
        echo "User: " . $router->URLAttribute("userId", "foo");
    }
}

class postRoutes {
    #[Route('/hello-world', 'post')]
    public function homePost(): void
    {
        echo "POST - Hello World";
    }
}

class crudRoutes {
    #[GET('/crud-hello-world')]
    public function homeCrudGET(): void
    {
        echo "CRUD GET - Hello World";
    }

    #[PUT('/crud-hello-world')]
    public function homeCrudPUT(): void
    {
        echo "CRUD PUT - Hello World";
    }

    #[POST('/crud-hello-world')]
    public function homeCrudPOST(): void
    {
        echo "CRUD POST - Hello World";
    }

    #[DELETE('/crud-hello-world')]
    public function homeCrudDELETE(): void
    {
        echo "CRUD DELETE - Hello World";
    }
}

$router = new Router();

function echoMessage(string $message): void
{
    echo $message;
}

$router->register404(fn() => echoMessage("page not found"));
$router->register("get", "/test", fn() => echoMessage("get test"));
$router->register("put", "/test", fn() => echoMessage("put test"));
$router->register("post", "/test", fn() => echoMessage("post test"));
$router->register("delete", "/test", fn() => echoMessage("delete test"));
$router->register("get", "/querystring", fn() => echoMessage($_GET["foo"] ?? ""));
$router->register("get", "/test/[0-9]+/foo", fn() => echoMessage("regex"));

$router->get("/crud-test", fn() => echoMessage("crud get test"));
$router->put("/crud-test", fn() => echoMessage("crud put test"));
$router->post("/crud-test", fn() => echoMessage("crud post test"));
$router->delete("/crud-test", fn() => echoMessage("crud delete test"));

$router->register("get", "/segment/[0-9]+", function () use ($router) {
    echo $router->getSegment(0);
});

$router->register("get", "/last/segment/[0-9]+", function () use ($router) {
    echo $router->getSegment(-1);
});

$router->register("get", "/second/segment/[0-9]+", function () use ($router) {
    echo $router->getSegment(1);
});

$router->register("get", "/books/{author}/{book_id}", function () use ($router) {
    $book_author = $router->URLAttribute("author");
    $book_id = $router->URLAttribute("book_id");
    echo "Author: $book_author ID: $book_id";
});

$router->registerRouteController(
    new routeWithPrependedSlug(),
    new getRoutes(),
    new postRoutes(),
    new crudRoutes()
);

$router->run();
