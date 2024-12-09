<?php

namespace AOWD;

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
        $this->methods = array_map(fn() => [], [
            'get',
            'put',
            'post',
            'delete'
        ]);

        $this->error_page = null;

        $this->path = !empty($_SERVER['REQUEST_URI'])
            ? parse_url($_SERVER['REQUEST_URI'])
            : false;

        if (isset($this->path['path'])) {
            $this->path['path'] = $this->formatRoute($this->path['path']);
        }
    }

    /**
     * Register new route
     * @param  string   $method
     * @param  string   $route
     * @param  callable $callback
     * @return boolean
     */
    public function register(string $method, string $route, callable $callback): bool
    {
        $method = strtolower($method);
        $route = $this->formatRoute($route);

        if (!$this->checkRoute($method, $route)) {
            $this->methods[$method][$route] = $callback;
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

        return isset($this->methods[$method][$route]) && is_callable($this->methods[$method][$route])
            ? $this->methods[$method][$route]
            : false;
    }

    /**
     * Run methods
     * @return void
     */
    public function run(): void
    {
        $route = $this->path['path'] ?? '';
        $method = strtolower($_SERVER['REQUEST_METHOD']);
        $callback = '';

        if (isset($this->methods[$method]) && !empty($route)) {
            foreach ($this->methods[$method] as $method_route => $method_callback) {
                $prepend = substr($method_route, 1) !== '^' ? '^' : '';
                $append = substr($method_route, -1) !== '$' ? '$' : '';
                $method_route = str_replace('/', '\/', $method_route);

                if (preg_match('/' . $prepend . $method_route . $append . '/', $route)) {
                    $callback = $method_callback;
                    break;
                }
            }
        } else {
            // Method is not supported
            http_response_code(501);
            exit();
        }

        if (is_callable($callback)) {
            http_response_code(200);
            $callback();
            exit();
        }

        $this->load404();
    }

    /**
     * Format route
     * @param  string $route
     * @return string
     */
    private function formatRoute(string $route): string
    {
        return $route . (
            strlen($route) > 1
            && substr($route, -1) !== '/'
            ? '/'
            : ''
        );
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
