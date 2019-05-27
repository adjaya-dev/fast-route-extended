<?php

declare(strict_types=1);

namespace Adjaya\FastRoute\Dispatcher;

class MarkBased extends RegexBasedAbstract
{
    public function __construct($data, $routesData = [])
    {
        list($this->staticRouteMap, $this->variableRouteData) = $data;
        $this->routesData = $routesData;
        //var_dump($this->routesData);
    }

    protected function dispatchVariableRoute($routeData, $uri)
    {
        foreach ($routeData as $data) {
            if (!preg_match($data['regex'], $uri, $matches)) {
                continue;
            }

            // Allow Multiple routes matching same regex
            $routes = [];
            foreach ($data['routeMap'][$matches['MARK']] as $key => $route) 
            {
                list($route_id, $varNames) = $route;

                $vars = [];
                $i = 0;
                foreach ($varNames as $varName) {
                    if (isset($matches[++$i])) {
                        $vars[$varName] = $matches[$i];
                    } else {
                        $vars[$varName] = null;
                    }
                }
                $routes[] = [$route_id, $vars];
            }

            return [self::FOUND, $routes];
        }

        return [self::NOT_FOUND, []];
    }
}
