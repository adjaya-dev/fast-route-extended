<?php

declare(strict_types=1);

namespace Adjaya\FastRoute;

class Collector implements CollectorInterface
{
    protected $routeCollector;

    public function __construct(RouteCollectorInterface $routeCollector)
    {
        $this->routeCollector = $routeCollector;
    }

    public function __call(string $method, array $params): HandlingGroup
    {
        var_dump($method);
        return $this->routeCollector->__call($method, $params);
    }

    public function group($prefix, callable $callback, CollectorDecoratorInterface $collector = null)
    {
        return $this->routeCollector->addGroup($prefix, $callback, $this);
    }

    public function route($httpMethods, $path, $handler)
    {
        return $this->routeCollector->addRoute($httpMethods, $path, $handler);
    }

        /**
     * Adds a GET route to the collection.
     *
     * This is simply an alias of $this->route('GET', $path, $handler)
     *
     * @param string $route
     * @param mixed  $handler
     */
    public function get($path, $handler)
    {
        return $this->route('GET', $path, $handler);
    }

    /**
     * Adds a POST route to the collection.
     *
     * This is simply an alias of $this->route('POST', $path, $handler)
     *
     * @param string $route
     * @param mixed  $handler
     */
    public function post($path, $handler)
    {
        return $this->route('POST', $path, $handler);
    }

    /**
     * Adds a PUT route to the collection.
     *
     * This is simply an alias of $this->route('PUT', $path, $handler)
     *
     * @param string $route
     * @param mixed  $handler
     */
    public function put($path, $handler)
    {
        return $this->route('PUT', $path, $handler);
    }

    /**
     * Adds a DELETE route to the collection.
     *
     * This is simply an alias of $this->route('DELETE', $path, $handler)
     *
     * @param string $route
     * @param mixed  $handler
     */
    public function delete($path, $handler)
    {
        return $this->route('DELETE', $path, $handler);
    }

    /**
     * Adds a PATCH route to the collection.
     *
     * This is simply an alias of $this->route('PATCH', $path, $handler)
     *
     * @param string $route
     * @param mixed  $handler
     */
    public function patch($path, $handler)
    {
        return $this->route('PATCH', $path, $handler);
    }

    /**
     * Adds a HEAD route to the collection.
     *
     * This is simply an alias of $this->route('HEAD', $path, $handler)
     *
     * @param string $route
     * @param mixed  $handler
     */
    public function head($path, $handler)
    {
        return $this->route('HEAD', $path, $handler);
    }

    public function any($path, $handler)
    {
        return $this->route('*', $path, $handler);
    }
}