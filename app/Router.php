<?php

declare(strict_types=1);

namespace App;

/**
 * Minimal HTTP router.
 *
 * Routes are registered with a path pattern that may contain {placeholders}.
 * Handlers are either a closure or a [ClassName::class, 'method'] pair; matched
 * placeholders are passed to the handler as an associative array.
 */
final class Router
{
    /** @var array<string, array<string, callable|array{0:class-string,1:string}>> */
    private array $routes = [];

    private string $basePath;

    public function __construct(?string $basePath = null)
    {
        // When the app lives in a subdirectory, strip that prefix from paths.
        $this->basePath = $basePath ?? rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
    }

    public function get(string $pattern, callable|array $handler): void
    {
        $this->add('GET', $pattern, $handler);
    }

    public function post(string $pattern, callable|array $handler): void
    {
        $this->add('POST', $pattern, $handler);
    }

    public function add(string $method, string $pattern, callable|array $handler): void
    {
        $this->routes[strtoupper($method)]['/' . trim($pattern, '/')] = $handler;
    }

    public function dispatch(string $method, string $path): void
    {
        $method = strtoupper($method);
        $path = $this->normalise($path);

        // HEAD is handled as GET; the SAPI discards the body.
        if ($method === 'HEAD') {
            $method = 'GET';
        }

        $allowedMethods = [];

        foreach ($this->routes as $routeMethod => $routes) {
            foreach ($routes as $pattern => $handler) {
                $params = $this->match($pattern, $path);
                if ($params === null) {
                    continue;
                }
                if ($routeMethod !== $method) {
                    $allowedMethods[] = $routeMethod;
                    continue;
                }
                $this->invoke($handler, $params);
                return;
            }
        }

        if ($allowedMethods !== []) {
            header('Allow: ' . implode(', ', array_unique($allowedMethods)));
            render_error_page(405, 'Method not allowed.');
            return;
        }

        render_error_page(404);
    }

    private function normalise(string $path): string
    {
        if ($this->basePath !== '' && str_starts_with($path, $this->basePath)) {
            $path = substr($path, strlen($this->basePath));
        }
        $path = '/' . trim($path, '/');
        return rawurldecode($path);
    }

    /**
     * @return array<string, string>|null  Matched params, or null if no match.
     */
    private function match(string $pattern, string $path): ?array
    {
        if (!str_contains($pattern, '{')) {
            return $pattern === $path ? [] : null;
        }

        $regex = preg_replace_callback(
            '/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/',
            static fn (array $m): string => '(?P<' . $m[1] . '>[^/]+)',
            $pattern
        );
        $regex = '#^' . $regex . '$#';

        if (preg_match($regex, $path, $matches) !== 1) {
            return null;
        }

        return array_filter(
            $matches,
            static fn ($key) => is_string($key),
            ARRAY_FILTER_USE_KEY
        );
    }

    private function invoke(callable|array $handler, array $params): void
    {
        if (is_array($handler)) {
            [$class, $method] = $handler;
            $handler = [new $class(), $method];
        }
        $handler($params);
    }
}
