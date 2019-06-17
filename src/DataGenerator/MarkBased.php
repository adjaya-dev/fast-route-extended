<?php

declare(strict_types=1);

namespace Adjaya\FastRoute\DataGenerator;

class MarkBased extends RegexBasedAbstract
{
    protected function getApproxChunkSize(): int
    {
        return 30;
    }

    protected function processChunk(array $regexToRoutesMap): array
    {
        $routeMap = [];
        $regexes = [];
        $markIndex = 0;
 
        foreach ($regexToRoutesMap as $regex => $_route) {
            // Allow Multiple routes matching same regex
            $routeStack = [];
            foreach ($_route as $key => $route) {
                // il peux y avoir plusieurs routes avec la même regex
                // donc on ne calcule la regex que pour la premiere!
                if ($key === 0) {
                    if (!$route->groupId) {
                        $regexes[] = $regex . '(*:' . $markIndex . ')';
                    } else {
                        $_regexes = & $regexes;
                        foreach ($route->prefixRegex as $prefix) {
                            $_regexes = & $_regexes[$prefix];
                        }
                        $_regexes[] = $route->regex . '(*:' . $markIndex . ')';
                    }
                    $current_group = $route->groupId;
                }
                $routeStack[] = [$route->id, $route->variables];
            }
 
            $routeMap[$markIndex] = $routeStack;

            ++$markIndex;
        }
        
        $regex = '~^(?' . $this->regexToString($regexes) . ')$~';

        return ['regex' => $regex, 'routeMap' => $routeMap];
    }

    protected function regexToString(array $regex_map): string
    {
        $regex = '';

        foreach ($regex_map as $k => $m) {
            if (is_string($k) && is_array($m)) { // group
                $regex .= sprintf("|$k(?%s)", $this->regexToString($m));
            } else { // route
                $regex .= '|'.$m;
            }
        }

        return $regex;
    }

    // Ancienne methode
    protected function _processChunk($regexToRoutesMap)
    {
        $routeMap = [];
        $regexes = [];
        $markIndex = 0;
        
        foreach ($regexToRoutesMap as $regex => $_route) {
            // Allow Multiple routes matching same regex
            $routeStack = [];
            foreach ($_route as $key => $route) {
                $routeStack[] = [$route->id, $route->variables];
            }
            $regexes[] = $regex . '(*:' . $markIndex . ')';
            $routeMap[$markIndex] = $routeStack;

            ++$markIndex;
        }

        $regex = '~^(?|' . implode('|', $regexes) . ')$~';
        return ['regex' => $regex, 'routeMap' => $routeMap];
    }
}
