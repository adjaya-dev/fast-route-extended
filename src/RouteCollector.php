<?php

declare(strict_types=1);

namespace Adjaya\FastRoute;

use Adjaya\FastRoute\Addon\RouteCollectorDecoratorAddonInterface;
use Adjaya\FastRoute\DataGenerator\DataGeneratorInterface;
use Adjaya\FastRoute\RouteParser\RouteParserInterface;
use Exception;

class RouteCollector implements RouteCollectorInterface
{
    protected $currentRouteId;
    protected $routeIdPrefix = 'route_';
    protected $routeIdCount = 0;

    protected $currentGroupId;
    protected $groupIdPrefix = 'group_';
    protected $groupIdCount = 0;

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
     * Constructs a route collector.
     *
     * @param RouteParserInterface   $routeParser
     * @param DataGeneratorInterface $dataGenerator
     */
    public function __construct(RouteParserInterface $routeParser, DataGeneratorInterface $dataGenerator)
    {
        $this->routeParser = $routeParser;
        $this->dataGenerator = $dataGenerator;
        $this->currentGroupPrefix = '';
    }

    /**
     * {@inheritdoc}
     *
     * Append Addons to routes data
     */
    public function getData(): array
    {
        $routes_data = $this->dataGenerator->getData();
        /*
        var_dump('**********$_routes_data******************');
        echo '<pre>';
        print_r($routes_data);
        echo '</pre>';
        */
        $routes_data['routes_data'] = $this->routesData;
        
        return $routes_data;
    }

    /**
     * {@inheritdoc}
     *
     * @param string|array $prefix
     */
    public function addGroup($prefix, callable $callback, 
        RouteCollectorDecoratorAddonInterface $collector = null): void
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

        if ($prefix) { 
            $previousGroupId = $this->currentGroupId;

            $this->currentGroupId = $this->groupIdPrefix .$this->groupIdCount++;

            $group_data = $this->routeParser->parse($prefix);

            $this->dataGenerator->addGroup($group_data, $this->currentGroupId, $previousGroupId);
        }

        $callback($collector);
        
        if ($prefix) { 
            $this->currentGroupId = $previousGroupId;
        }
        
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
        $this->currentRouteId = $this->routeIdPrefix . $this->routeIdCount ++;
        $route_name = '';

        if ($this->currentGroupName) {
            $route_name = $this->currentGroupName;
        }

        if (\is_array($route)) {
            $route_name .= $route_name ? '.' . key($route) : key($route);

            $route = $route[key($route)];
        }

        $currentRoute = $this->currentGroupPrefix . $route;
       
        $route_data = $this->routeParser->parse($currentRoute);

        $this->dataGenerator->addRoute($httpMethod, $route_data, $this->currentRouteId, $this->currentGroupId);

        /** PARSE REVERSE */
        if ($route_name && method_exists($this->routeParser, 'parseReverse')) {

            if (isset($this->routesData['reverse']) &&
                \in_array($route_name, $this->routesData['reverse'], true)
            ) {
                throw new Exception(
                    "The route name '$route_name' is already used and must be unique!"
                );
            }

            $this->routesData['reverse'][$route_name] =
                $this->routeParser->parseReverse($route_data);

            $this->routesData['named'][$this->currentRouteId] = $route_name;
        }

        $this->routesData['info'][$this->currentRouteId]['handler'] = $handler;
    }

    public function getCurrentRouteId(): string 
    {
        return $this->currentRouteId;
    }

    public function getCurrentGroupId(): ?string 
    {
        return $this->currentGroupId;
    }

    /**
     * Adds a GET route to the collection
     * 
     * This is simply an alias of $this->addRoute('GET', $route, $handler)
     *
     * @param string $route
     * @param mixed  $handler
     */
    public function get($route, $handler)
    {
        $this->addRoute('GET', $route, $handler);
    }    

    /**
     * Adds a POST route to the collection
     * 
     * This is simply an alias of $this->addRoute('POST', $route, $handler)
     *
     * @param string $route
     * @param mixed  $handler
     */
    public function post($route, $handler)
    {
        $this->addRoute('POST', $route, $handler);
    }

    /**
     * Adds a PUT route to the collection
     * 
     * This is simply an alias of $this->addRoute('PUT', $route, $handler)
     *
     * @param string $route
     * @param mixed  $handler
     */
    public function put($route, $handler)
    {
        $this->addRoute('PUT', $route, $handler);
    }

    /**
     * Adds a DELETE route to the collection
     * 
     * This is simply an alias of $this->addRoute('DELETE', $route, $handler)
     *
     * @param string $route
     * @param mixed  $handler
     */
    public function delete($route, $handler)
    {
        $this->addRoute('DELETE', $route, $handler);
    }

    /**
     * Adds a PATCH route to the collection
     * 
     * This is simply an alias of $this->addRoute('PATCH', $route, $handler)
     *
     * @param string $route
     * @param mixed  $handler
     */
    public function patch($route, $handler)
    {
        $this->addRoute('PATCH', $route, $handler);
    }

    /**
     * Adds a HEAD route to the collection
     *
     * This is simply an alias of $this->addRoute('HEAD', $route, $handler)
     *
     * @param string $route
     * @param mixed  $handler
     */
    public function head($route, $handler)
    {
        $this->addRoute('HEAD', $route, $handler);
    }

    public function any($route, $handler)
    {
        $this->addRoute('*', $route, $handler);
    }
}
