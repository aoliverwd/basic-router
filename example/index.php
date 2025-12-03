<?php

namespace AOWD\Example;

use AOWD\Router;
use AOWD\Attributes\Route;
use AOWD\Attributes\Middleware;
use AOWD\Interfaces\Middleware as MiddlewareInterface;

include_once dirname(__DIR__) . "/vendor/autoload.php";

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
        echo "User: " . $router->URLAttribute("userId")
        ." Order: " . $router->URLAttribute("orderId");
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

$router->register("get", "/segment/[0-9]+", function () use ($router) {
    echo $router->getSegment(0);
});

$router->register("get", "/last/segment/[0-9]+", function () use ($router) {
    echo $router->getSegment(-1);
});

$router->register("get", "/second/segment/[0-9]+", function () use ($router) {
    echo $router->getSegment(1);
});

$router->registerRouteController(new getRoutes(), new postRoutes());
$router->run();
