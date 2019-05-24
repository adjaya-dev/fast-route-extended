<?php

declare(strict_types=1);

namespace Adjaya\FastRoute\Addon;

use Adjaya\FastRoute\Handling\HandlingInterface;

class RouteCollectorDecoratorAddonBase implements RouteCollectorDecoratorAddonInterface
{
    /**
     * @var RouteCollector
     */
    protected $RouteCollector;

    protected $options;

    public function __construct(RouteCollectorDecoratorAddonInterface $RouteCollector,
        ?array $options = null)
    {
        $this->RouteCollector = $RouteCollector;

        if (!empty($options)) { $this->setOptions($options); }
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
    public function groupAddons(callable $callback,
        RouteCollectorDecoratorAddonInterface $collector = null): HandlingInterface
    {
        return $this->addGroup('', $callback, $collector);
    }

    public function addGroup($prefix, callable $callback,
        RouteCollectorDecoratorAddonInterface $collector = null): HandlingInterface
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
    
    public function any($route, $handler): HandlingInterface {
        return $this->addRoute('*', $route, $handler);
    }
}
