<?php

declare(strict_types=1);

namespace Adjaya\FastRoute;

class RouteCollectorDecoratorBase implements RouteCollectorDecoratorInterface
{
    /**
     * @var RouteCollector
     */
    protected $RouteCollector;

    protected $options;

    public function __construct(
        RouteCollectorDecoratorInterface $RouteCollector, ?array $options = null
    ) {
        $this->RouteCollector = $RouteCollector;

        if (!empty($options)) {
            $this->setOptions($options);
        }
    }

    protected function setOptions(array $options): void
    {
        $this->options = $options;
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
    public function groupAddons(
        callable $callback, RouteCollectorDecoratorInterface $collector = null
    ): HandlingInterface {
        return $this->addGroup('', $callback, $collector);
    }

    public function addGroup(
        $prefix, callable $callback, RouteCollectorDecoratorInterface $collector = null
    ): HandlingGroupInterface {
        if (!$collector) {
            $collector = $this;
        }

        return $this->RouteCollector->addGroup($prefix, $callback, $collector);
    }
    
    public function addRoute($httpMethod, $route, $handler): HandlingRouteInterface
    {
        return $this->RouteCollector->addRoute($httpMethods, $route, $handler);
    }
    /*
    public function getCurrentRouteId(): string
    {
        return $this->RouteCollector->getCurrentRouteId();
    }
    */

    public function get($route, $handler)
    {
        return $this->addRoute('GET', $route, $handler);
    }

    public function post($route, $handler)
    {
        return $this->addRoute('POST', $route, $handler);
    }

    public function put($route, $handler)
    {
        return $this->addRoute('PUT', $route, $handler);
    }

    public function delete($route, $handler)
    {
        return $this->addRoute('DELETE', $route, $handler);
    }

    public function patch($route, $handler)
    {
        return $this->addRoute('PATCH', $route, $handler);
    }

    public function head($route, $handler)
    {
        return $this->addRoute('HEAD', $route, $handler);
    }

    public function any($route, $handler): HandlingInterface
    {
        return $this->addRoute('*', $route, $handler);
    }
}
