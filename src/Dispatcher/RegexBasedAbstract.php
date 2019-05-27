<?php

declare(strict_types=1);

namespace Adjaya\FastRoute\Dispatcher;

use Psr\Http\Message\RequestInterface;

abstract class RegexBasedAbstract implements DispatcherInterface
{
    /** @var mixed[][] */
    protected $staticRouteMap = [];

    /** @var mixed[] */
    protected $variableRouteData = [];

    protected $routeData = [];

    /**
     * @return mixed[]
     */
    abstract protected function dispatchVariableRoute($routeData, $uri);

    protected function dispatchStaticRoute($routeData)
    {
        // Allow Multiple routes matching same regex
        $routes = [];
        foreach ($routeData as $key => $route_id) {
            $routes[] = [$route_id, []];  
        }
        
        return [self::FOUND, $routes];
    }

    public function dispatch($httpMethod, $uri, ?RequestInterface $Request = null): array 
    {
        $result = $this->_dispatch($httpMethod, $uri);
        switch ($result[0]) {
            case self::NOT_FOUND:
                return $result;
                break;
            case self::METHOD_NOT_ALLOWED:
                return $result;
                break;
            case self::FOUND:
                foreach ($result[1] as $key => &$route) {
                    $route_id = $route[0];
                    $route_data = $this->routesData['info'][$route_id];
                    $route['_route'] = $route_data;
                }
                return $result;
                break;
        }
    }

    protected function _dispatch($httpMethod, $uri)
    {
        if ($httpMethod === 'HEAD') { $httpMethod = 'GET'; }

        if (isset($this->staticRouteMap[$httpMethod][$uri])) {
            
            $result = $this->dispatchStaticRoute($this->staticRouteMap[$httpMethod][$uri]);
            if ($result[0] === self::FOUND) {
                return $result;
            }
        }

        $varRouteData = $this->variableRouteData;
        if (isset($varRouteData[$httpMethod])) {
            $result = $this->dispatchVariableRoute($varRouteData[$httpMethod], $uri);
            if ($result[0] === self::FOUND) {
                return $result;
            }
        }

        // If nothing else matches, try fallback routes
        if (isset($this->staticRouteMap['*'][$uri])) {

            $result = $this->dispatchStaticRoute($this->staticRouteMap['*'][$uri]);
            if ($result[0] === self::FOUND) {
                return $result;
            }
        }

        if (isset($varRouteData['*'])) {
            $result = $this->dispatchVariableRoute($varRouteData['*'], $uri);
            if ($result[0] === self::FOUND) {
                return $result;
            }
        }

        // Find allowed methods for this URI by matching against all other HTTP methods as well
        $allowedMethods = [];

        foreach ($this->staticRouteMap as $method => $uriMap) {
            if ($method !== $httpMethod && isset($uriMap[$uri])) {
                $allowedMethods[] = $method;
            }
        }

        foreach ($varRouteData as $method => $routeData) {
            if ($method === $httpMethod) {
                continue;
            }

            $result = $this->dispatchVariableRoute($routeData, $uri);
            if ($result[0] === self::FOUND) {
                $allowedMethods[] = $method;
            }
        }

        // If there are no allowed methods the route simply does not exist
        if ($allowedMethods) {
            return [self::METHOD_NOT_ALLOWED, $allowedMethods];
        }

        return [self::NOT_FOUND, null];
    }
}
