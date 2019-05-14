<?php

namespace Adjaya\FastRoute\DataGenerator;

class MarkBased extends RegexBasedAbstract
{
    protected function getApproxChunkSize()
    {
        return 30;
    }

    protected function processChunk($regexToRoutesMap)
    {
        $routeMap = [];
        $regexes = [];
        $markIndex = 0;
        
        foreach ($regexToRoutesMap as $regex => $_route) {
            // Allow Multiple routes matching same regex
            $routeStack = [];
            foreach ($_route as $key => $route) {
                $routeStack[] = [$route->handler, $route->variables];
            }
            $regexes[] = $regex . '(*:' . $markIndex . ')';
            $routeMap[$markIndex] = $routeStack;

            ++$markIndex;
        }

        $regex = '~^(?|' . implode('|', $regexes) . ')$~';
        return ['regex' => $regex, 'routeMap' => $routeMap];
    }
}
