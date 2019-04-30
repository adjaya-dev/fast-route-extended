<?php

declare(strict_types=1);

namespace Adjaya\FastRoute;

class RouteCollectorDecoratorBase implements RouteCollectorDecoratorInterface
{
    /**
     * @var RouteCollector
     */
    protected $RouteCollector;

    public function __construct(RouteCollectorDecoratorInterface $RouteCollector)
    {
        $this->RouteCollector = $RouteCollector;
    }
    
    public function getData(): array
    {
        return $this->RouteCollector->getData();
    }

    /**
     * Create an addons group.
     * 
     * @param callable $callback
     */
    public function groupAddons(callable $callback,
        RouteCollectorDecoratorInterface $collector = null): HandlingInterface
    {
        if (!$collector) { $collector = $this; }

        return $this->addGroup('', $callback, $collector);
    }

    public function addGroup($prefix, callable $callback,
        RouteCollectorDecoratorInterface $collector = null): HandlingInterface
    {
        if (!$collector) { $collector = $this; }

        return $this->RouteCollector->addGroup($prefix, $callback, $collector);
    }

    public function addRoute($httpMethod, $route, $handler): HandlingInterface
    {
        return $this->RouteCollector->addRoute($httpMethod, $route, $handler);
    }

    public function getCurrentRouteId(): string 
    {
        return $this->RouteCollector->getCurrentRouteId();
    }

    public function get($route, $handler) : HandlingInterface {
        return $this->addRoute('GET', $route, $handler);
    }

    public function post($route, $handler): HandlingInterface {
        return $this->addRoute('POST', $route, $handler);
    }

    public function put($route, $handler): HandlingInterface {
        return $this->addRoute('PUT', $route, $handler);
    }

    public function delete($route, $handler): HandlingInterface {
        return $this->addRoute('DELETE', $route, $handler);
    }

    public function patch($route, $handler): HandlingInterface {
        return $this->addRoute('PATCH', $route,$handler);
    }

    public function head($route, $handler): HandlingInterface {
        return $this->addRoute('HEAD', $route, $handler);
    }   
}
