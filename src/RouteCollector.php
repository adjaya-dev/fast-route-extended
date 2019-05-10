<?php

declare(strict_types=1);

namespace Adjaya\FastRoute;

use Exception;
use FastRoute\Datagenerator;
use FastRoute\RouteCollector as FastRouteCollector;
use FastRoute\RouteParser;

class RouteCollector extends FastRouteCollector implements RouteCollectorInterface
{
    protected $currentRouteId;
    protected $routeIdPrefix = 'route_';
    protected $routeIdCount = 0;
    
    /**
     * Routes Data.
     *
     * @var array
     */
    protected $routesData;

    /**
     * @var string
     */
    protected $currentGroupName;

    /**
     * {@inheritdoc}
     *
     * Append Addons to routes data
     */
    public function getData(): array
    {
        $routes_data = parent::getData();

        $routes_data['routes_data'] = $this->routesData;
        
        return $routes_data;
    }

    /**
     * {@inheritdoc}
     *
     * @param string|array $prefix
     */
    public function addGroup($prefix, callable $callback, RouteCollectorDecoratorAddonInterface $collector = null): void
    {
        if (!$collector) { $collector = $this; }

        if (\is_array($prefix)) {
            $group_name = key($prefix);
            $prefix = $prefix[$group_name];
        }

        $previousGroupName = $this->currentGroupName;

        if (isset($group_name)) {
            $this->currentGroupName =
                $previousGroupName ? $previousGroupName . '.' . $group_name : $group_name;
        }

        $previousGroupPrefix = $this->currentGroupPrefix;
        $this->currentGroupPrefix = $previousGroupPrefix . $prefix;

        $callback($collector);

        $this->currentGroupPrefix = $previousGroupPrefix;
 
        $this->currentGroupName = $previousGroupName;
    }

    /**
     * {@inheritdoc}
     *
     * @param string|array $route
     * 
     * @return string $route_id
     */
    public function addRoute($httpMethod, $route, $handler): void
    {
        $this->currentRouteId = $this->routeIdPrefix.$this->routeIdCount++;
        $route_name = '';

        if ($this->currentGroupName) {
            $route_name = $this->currentGroupName;
        }

        if (\is_array($route)) {
            $route_name .= $route_name ? '.' . key($route) : key($route);

            $route = $route[key($route)];
        }

        $route_id = '';
        foreach ((array) $httpMethod as $method) {
            $route_id .= $method . '.';
        }

        $currentRoute = $this->currentGroupPrefix . $route;
        $route_id .= $currentRoute;

        $routesData = $this->routeParser->parse($currentRoute);

        foreach ((array) $httpMethod as $method) {
            foreach ($routesData as $routeData) {
                $this->dataGenerator->addRoute($method, $routeData, $this->currentRouteId);
            }
        }

        if ($route_name && method_exists($this->routeParser, 'parseReverse')) {
            if (isset($this->routesData['reverse']) &&
                \in_array($route_name, $this->routesData['reverse'], true)
            ) {
                throw new Exception(
                    "The route name '$route_name' is already used and must be unique!"
                );
            }

            $this->routesData['reverse'][$route_name] =
                $this->routeParser->parseReverse($routesData);

            $this->routesData['named'][$this->currentRouteId] = $route_name;
        }

        $this->routesData['info'][$this->currentRouteId]['handler'] = $handler;

        //$this->currentRouteId = $route_id;
    }

    public function getCurrentRouteId(): string 
    {
        return $this->currentRouteId;
    }
}
