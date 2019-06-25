<?php

declare(strict_types=1);

namespace Adjaya\FastRoute;

use Adjaya\FastRoute\DataGenerator\DataGeneratorInterface;
use Adjaya\FastRoute\RouteParser\RouteParserInterface;
use Exception;

class RouteCollector implements RouteCollectorInterface
{
    protected $currentGroupId;
    protected $groupIdPrefix = 'group_';
    protected $groupIdCount = 0;
    protected $currentGroup;
    protected $currentHandlingGroup;

    /**
     * Routes Data.
     *
     * @var array
     */
    protected $routesData = [];

    protected $currentGroupPrefix = '';

    /**
     * @var string
     */
    protected $currentGroupName = '';

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
        $this->currentGroup = new Group(); // Main group
        $this->currentHandlingGroup = new HandlingGroup($this->currentGroup);
    }

    public function __call(string $method, array $params): HandlingGroup
    {
        try {
            return call_user_func_array(
                [$this->currentHandlingGroup, $method], $params
            );
        } catch (Throwable $e) {
            throw new exception ($e->getMessage());
        }
    }

    /**
     * {@inheritdoc}
     *
     * Generate Main Group data
     */
    public function getData(): array
    {
        $this->groupDataGenerator($this->currentGroup);

        $routes_data = $this->dataGenerator->getData();

        $routes_data['routes_data'] = $this->routesData;

        return $routes_data;
    }

    protected function groupDataGenerator($group)
    {
        $this->processCollection($group->getCollection());
    }

    protected function processCollection($collection) 
    {
        foreach ($collection as $obj) 
        {
            if ($obj instanceof Route) {

                $currentRoute = $this->currentGroupPrefix . $obj->getPath();
                $route_data = $this->routeParser->parse($currentRoute);

                $this->dataGenerator
                    ->addRoute($obj->getHttpMethods(), $route_data, $obj->getId(), $this->currentGroupId);

                /* PARSE REVERSE */
                $this->parseReverse($obj, $route_data);

                $this->routesData['info'][$obj->getId()]['handler'] = $obj->getHandler();

            } elseif($obj instanceof Group) {

                $previousGroupName = $this->currentGroupName;
                if ($name = $obj->getName()) {
                    $this->currentGroupName = $previousGroupName ? $previousGroupName . '.' . $name : $name;
                }

                $previousGroupPrefix = $this->currentGroupPrefix;
                $this->currentGroupPrefix = $previousGroupPrefix . $obj->getPrefix();

                if ($prefix = $obj->getPrefix())
                {
                    $previousGroupId = $this->currentGroupId;
                    $this->currentGroupId = $this->groupIdPrefix . $this->groupIdCount++;

                    $group_data = $this->routeParser->parse($prefix);
                    $this->dataGenerator->addGroup($group_data, $this->currentGroupId, $previousGroupId);
                }

                $this->processCollection($obj->getCollection());

                if ($prefix) {
                    $this->currentGroupId = $previousGroupId;
                }

                $this->currentGroupPrefix = $previousGroupPrefix;
                $this->currentGroupName = $previousGroupName;

            } else {
                throw new Exception("Error Processing Request", 7);
            }
        }
    }

    protected function parseReverse($obj, $route_data)
    {
        if (($name = $obj->getName()) && method_exists($this->routeParser, 'parseReverse')) 
        {
            $route_name = null;

            if ($this->currentGroupName) {
                $route_name = $this->currentGroupName;
            }

            $route_name .= $route_name ? '.' . $name : $name;

            if (isset($this->routesData['reverse']) &&
                array_key_exists($route_name, $this->routesData['reverse'])
            ) {
                throw new Exception(
                    "The route name '$route_name' is already used and must be unique!"
                );
            }

            $this->routesData['reverse'][$route_name] = $this->routeParser->parseReverse($route_data);

            $this->routesData['named'][$obj->getId()] = $route_name;
        }        
    }

    /**
     * {@inheritdoc}
     *
     * @param string|array $prefix
     */
    public function addGroup(
        $prefix, callable $callback, CollectorInterface $collector
    ): HandlingGroupInterface
    {
        if (!$collector) {
            $collector = $this;
        }

        $group_name = '';
        if (\is_array($prefix)) {
            $group_name = key($prefix);
            $prefix = $prefix[$group_name];
        }

        $previousGroup = $this->currentGroup;
        $group = $this->currentGroup = new Group($prefix, $group_name);

        $previousHandlingGroup = $this->currentHandlingGroup;
        $groupHandling = $this->currentHandlingGroup = new HandlingGroup($group);

        $previousGroup->addGroup($group);          

        $callback($collector);

        $this->currentGroup = $previousGroup;
        $this->currentHandlingGroup = $previousHandlingGroup;

        return $groupHandling;
    }

    /**
     * {@inheritdoc}
     *
     * @param string|array $route
     *
     * @return string $route_id
     */
    public function addRoute($httpMethods, $path, $handler): HandlingRouteInterface
    {
        $name = '';
        if (\is_array($path)) {
            $name = key($path);
            $path = $path[key($path)];
        }

        $route = new Route(
                    $httpMethods,
                    $path,
                    $handler,
                    $name
                );

        $this->currentGroup->addRoute($route);
        
        return new HandlingRoute($route);
    }
    /*
    public function getCurrentRouteId(): string
    {
        return $this->currentRouteId;
    }

    public function getCurrentGroupId(): ?string
    {
        return $this->currentGroupId;
    }
    */
}
