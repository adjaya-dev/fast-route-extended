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
        $markName = 'a';
        foreach ($regexToRoutesMap as $regex => $_route) {
            // Allow Multiple routes matching same regex
            $routeStack = [];
            foreach ($_route as $key => $route) {
                $routeStack[] = [$route->handler, $route->variables];
            }
            $regexes[] = $regex . '(*MARK:' . $markName . ')';
            $routeMap[$markName] = $routeStack;

            ++$markName;
        }

        $regex = '~^(?|' . implode('|', $regexes) . ')$~';
        return ['regex' => $regex, 'routeMap' => $routeMap];
    }
}
