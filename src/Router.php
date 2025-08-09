<?php

namespace AOWD;

use AOWD\Interfaces\Middleware as MiddlewareInterface;
use AOWD\Attributes\Middleware;
use AOWD\Attributes\Route;
use ReflectionClass;

class Router
{
    /** @var array<mixed> */
    private array $methods;

    /** @var mixed */
    private mixed $error_page;

    /** @var array<mixed> $path */
    private array|false $path;


    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->methods = array_map(fn () => [], ["get", "put", "post", "delete"]);
        $this->error_page = null;
        $this->path = !empty($_SERVER["REQUEST_URI"]) ? parse_url($_SERVER["REQUEST_URI"]) : false;

        if (isset($this->path["path"])) {
            $this->path["path"] = $this->formatRoute($this->path["path"]);
        }
    }

    /**
     * Register a controller and its route attributes
     * @param  object $controller
     * @return void
     */
    public function registerRouteController(object $controller): void
    {
        $refClass = new ReflectionClass($controller);

        foreach ($refClass->getMethods() as $method) {
            foreach ($method->getAttributes(Route::class) as $attr) {
                $routeAttr = $attr->newInstance();
                $path = $this->formatRoute($routeAttr->path);
                $httpMethod = strtoupper($routeAttr->method);
                $method_name = $method->getName();

                // Collect middleware for this method
                $middlewareList = [];
                foreach ($method->getAttributes(Middleware::class) as $mwAttr) {
                    $middlewareList[] = $mwAttr->newInstance()->className;
                }

                if (!empty($method_name)) {
                    // Now register the route and attach middleware metadata
                    $this->register($httpMethod, $path, [$controller, $method->getName()], $middlewareList);
                }
            }
        }
    }

    /**
     * Register new route
     * @param  string   $method
     * @param  string   $route
     * @param  callable|array<mixed> $callback
     * @param  array<mixed> $middleware
     * @return boolean
     */
    public function register(string $method, string $route, callable|array $callback, array $middleware = []): bool
    {
        $method = strtolower($method);
        $route = $this->formatRoute($route);

        if (!$this->checkRoute($method, $route)) {
            $this->methods[$method][$route] = [
                'handler' => $callback,
                'middleware' => $middleware
            ];
            return true;
        }

        return false;
    }

    /**
     * Unregister route
     * @param  string $method
     * @param  string $route
     * @return boolean
     */
    public function unregister(string $method, string $route): bool
    {
        $route = $this->formatRoute($route);

        if ($this->checkRoute($method, $route)) {
            unset($this->methods[$method][$route]);
            $result = $this->checkRoute($method, $route);
            return is_bool($result) && !$result ? true : false;
        }

        return false;
    }

    /**
     * Register 404 callback
     * @param  callable $callback
     * @return void
     */
    public function register404(callable $callback): void
    {
        $this->error_page = $callback;
    }

    /**
     * Check route exists in method
     * @param  string $method
     * @param  string $route
     * @return callable|boolean
     */
    public function checkRoute(string $method, string $route): callable|bool
    {
        $route = $this->formatRoute($route);

        return isset($this->methods[$method][$route]['handler'])
            && is_callable($this->methods[$method][$route]['handler'])
            ? $this->methods[$method][$route]['handler']
            : false;
    }

    /**
     * Run methods
     * @return void
     */
    public function run(): void
    {
        $route = $this->path["path"] ?? false;
        $method = strtolower($_SERVER["REQUEST_METHOD"]);
        $callback = "";

        // Method is not supported
        if ($route && !isset($this->methods[$method])) {
            http_response_code(501);
            exit();
        }

        foreach ($this->methods[$method] as $method_route => $method_callback) {
            $prepend = substr($method_route, 1) !== "^" ? "^" : "";
            $append = substr($method_route, -1) !== '$' ? '$' : "";
            $method_route = str_replace("/", "\/", $method_route);

            if (preg_match("/" . $prepend . $method_route . $append . "/", $route)) {
                $callback = $method_callback;
                break;
            }
        }

        // Check if $callback is array
        if (is_array($callback)) {
            // Execute middleware
            foreach ($callback['middleware'] as $mwClass) {
                if (class_exists($mwClass) && $implements = class_implements($mwClass)) {
                    if (isset($implements[MiddlewareInterface::class]) && method_exists($mwClass, 'handle')) {
                        new $mwClass()->handle();
                    }
                }
            }

            // Run route handler
            http_response_code(200);
            $callback['handler']($this);
            exit();
        }

        $this->load404();
    }

    /**
     * Get segment from route
     * @param  int $segmentID
     * @return string
     */
    public function getSegment(int $segmentID): string
    {
        $segments = is_array($this->path) && isset($this->path["path"]) ? explode("/", $this->path["path"]) : [];

        if (empty($segments)) {
            return "";
        }

        $segments = array_values(array_filter($segments));

        if ($segmentID < 0) {
            return $segments[count($segments) - abs($segmentID)] ?? "";
        }

        return isset($segments[$segmentID]) ? $segments[$segmentID] : "";
    }

    /**
     * Format route
     * @param  string $route
     * @return string
     */
    private function formatRoute(string $route): string
    {
        return $route . (strlen($route) > 1 && substr($route, -1) !== "/" ? "/" : "");
    }

    /**
     * Load 404 page
     * @return void
     */
    private function load404(): void
    {
        http_response_code(404);
        if (is_callable($this->error_page)) {
            call_user_func($this->error_page);
        }

        exit();
    }
}
