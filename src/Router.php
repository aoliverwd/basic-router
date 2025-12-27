<?php

namespace AOWD;

use AOWD\Interfaces\Middleware as MiddlewareInterface;
use AOWD\Interfaces\Route as RouteInterface;
use AOWD\Attributes\Middleware;
use AOWD\Attributes\Route;
use AOWD\Attributes\DELETE;
use AOWD\Attributes\POST;
use AOWD\Attributes\GET;
use AOWD\Attributes\PUT;
use ReflectionAttribute;
use ReflectionMethod;
use Rize\UriTemplate;
use ReflectionClass;

require_once dirname(__DIR__) . '/vendor/autoload.php';

final class Router
{
    /** @var array<mixed> */
    private array $methods;

    /** @var mixed */
    private mixed $error_page;

    /** @var array<mixed> $path */
    public array|false $path;

    /** @var array<string, array<string, string|int>> $url_attributes */
    private array $url_attributes = [];

    /** @var array<string, string|int> $url_attribute_data */
    private array $url_attribute_data;

    /** URI Template class */
    private readonly UriTemplate $uri_template;

    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->methods = array_map(fn () => [], ["get", "put", "post", "delete"]);
        $this->error_page = null;
        $this->path = !empty($_SERVER["REQUEST_URI"])
            ? parse_url($_SERVER["REQUEST_URI"])
            : false;

        // URI Template class
        $this->uri_template = new UriTemplate();

        // Sanitize path
        if (isset($this->path["path"])) {
            $this->path["path"] = $this->formatRoute(htmlentities($this->path["path"], ENT_QUOTES, 'UTF-8'));
        }

        // Sanitize query parameters
        if (isset($this->path["query"])) {
            parse_str($this->path["query"], $this->path["query"]);
            $this->path["query"] = array_map(function ($value) {
                return is_scalar($value) ? htmlentities($value, ENT_QUOTES, 'UTF-8') : "";
            }, $this->path["query"]);
        }
    }

    // Return Sanitized URI string
    private function safeURI(): string
    {
        return ($this->path["path"] ?? "") . (isset($this->path["query"]) ? "?" . http_build_query($this->path['query']) : "");
    }

    /**
     * Register a controller and its route attributes
     * @param  object $controllers
     */
    public function registerRouteController(...$controllers): void
    {
        foreach ($controllers as $controller) {
            if (class_exists($controller::class)) {
                $refClass = new ReflectionClass($controller);

                $prepend_path = $controller instanceof RouteInterface
                    ? $controller->prepend_path
                    : "";

                foreach ($refClass->getMethods() as $method) {
                    foreach ($this->reflectionClassAttributes($method) as $attr) {
                        if ($attr instanceof ReflectionAttribute) {
                            $routeAttr = $attr->newInstance();
                            $path = $this->formatRoute($prepend_path . ($routeAttr->path ?? ""));
                            $httpMethod = strtoupper($routeAttr->method ?? "");
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
            }
        }
    }

    /**
     * Return class attributes from reflection method
     * @param  ReflectionMethod $method
     * @return array<int, ReflectionAttribute<DELETE|GET|POST|PUT|Route>>|array<null>
     */
    private function reflectionClassAttributes(ReflectionMethod $method): array
    {
        $attribute_classes = [
            Route::class,
            GET::class,
            PUT::class,
            POST::class,
            DELETE::class,
        ];

        foreach ($attribute_classes as $class) {
            $methods = $method->getAttributes($class);
            if ($methods) {
                return $methods;
            }
        }

        return [];
    }

    /**
     * Register new route
     * @param  string $method http method
     * @param  callable|array<mixed> $callback
     * @param  array<mixed> $middleware
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
     * Register get request helper method
     * @param  callable|array<mixed> $callback
     * @param  array<mixed> $middleware
     */
    public function get(string $route, callable|array $callback, array $middleware = []): void
    {
        $this->register('get', $route, $callback, $middleware);
    }

    /**
     * Register get request helper method
     * @param  callable|array<mixed> $callback
     * @param  array<mixed> $middleware
     */
    public function put(string $route, callable|array $callback, array $middleware = []): void
    {
        $this->register('put', $route, $callback, $middleware);
    }

    /**
     * Register get request helper method
     * @param  callable|array<mixed> $callback
     * @param  array<mixed> $middleware
     */
    public function post(string $route, callable|array $callback, array $middleware = []): void
    {
        $this->register('post', $route, $callback, $middleware);
    }

    /**
     * Register get request helper method
     * @param  callable|array<mixed> $callback
     * @param  array<mixed> $middleware
     */
    public function delete(string $route, callable|array $callback, array $middleware = []): void
    {
        $this->register('delete', $route, $callback, $middleware);
    }

    /**
     * Unregister route
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
     */
    public function register404(callable $callback): void
    {
        $this->error_page = $callback;
    }

    /**
     * Check route exists in method
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
            $new_method_route = str_replace(["/", "?"], ["\/", "\?"], $method_route);

            if (
                $this->isURITemplate($method_route)
                && $this->uri_template->extract($method_route, $this->safeURI(), true)
                || preg_match("/" . $prepend . $new_method_route . $append . "/", $route)
            ) {
                if (isset($this->url_attributes[$method_route])) {
                    $this->url_attribute_data = $this->url_attributes[$method_route];
                }

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
     */
    public function getSegment(int|string $segmentIdentifier): string|int
    {
        // Identifer id is a URL attribute
        if (is_string($segmentIdentifier)) {
            return $this->URLAttribute($segmentIdentifier);
        }

        $segments = is_array($this->path) && isset($this->path["path"]) ? explode("/", $this->path["path"]) : [];

        if (empty($segments)) {
            return "";
        }

        $segments = array_values(array_filter($segments));

        if ($segmentIdentifier < 0) {
            return $segments[count($segments) - abs($segmentIdentifier)] ?? "";
        }

        return isset($segments[$segmentIdentifier]) ? $segments[$segmentIdentifier] : "";
    }

    /**
     * Format route URL
     */
    private function formatRoute(string $route): string
    {
        if (!preg_match('/\?/', $route)) {
            $route .= (strlen($route) > 1 && substr($route, -1) !== "/" ? "/" : "");
        }

        return $this->registerURLAttributes($route);
    }

    /**
     * Check if provided string is a URI template
     */
    private function isURITemplate(string $route): bool
    {
        return preg_match_all('/\{(.*?)\}/', $route) ? true : false;
    }

    private function hasQueryString(string $route): bool
    {
        return preg_match('/\{\?(.*?)\}|\?/', $route) ? true : false;
    }

    /**
     * Register URL attributes I.E /search/{term:1}/{term}/{?q*,limit}
     */
    private function registerURLAttributes(string $route): string
    {
        if ($this->isURITemplate($route)) {
            $path = $this->path['path'] ?? "";

            // Has query string
            if ($this->hasQueryString($route)) {
                $path = $this->safeURI();
            }

            $params = $this->uri_template->extract($route, $path, true);

            if ($params) {
                $this->url_attributes[$route] = $params;
            }
        }

        return $route;
    }

    /**
     * Return URL attribute
     */
    public function URLAttribute(string $attribute_name, string|int $fallback = ""): string|int
    {
        return $this->url_attribute_data[$attribute_name] ?? $fallback;
    }

    /**
     * Returns a sanitized value for the specified query-string parameter ($_GET['q'])
     */
    public function getParameter(string $parameter): string|int
    {
        return $this->path["query"][$parameter] ?? "";
    }

    /**
     * Load 404 page
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
