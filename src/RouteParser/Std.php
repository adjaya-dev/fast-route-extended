<?php

declare(strict_types=1);

namespace Adjaya\FastRoute\RouteParser;

use Adjaya\FastRoute\ReverseRouter;
use LogicException;
use RuntimeException;

class Std implements RouteParserInterface
{
    const VARIABLE_REGEX = <<<'REGEX'
\{
    \s* ([a-zA-Z_][a-zA-Z0-9_-]*) \s*
    (?:
        : \s* ([^{}]*(?:\{(?-1)\}[^{}]*)*)
    )?
\}
REGEX;

    const DEFAULT_DISPATCH_REGEX = '[^/]+';

    public function parse($route)
    {
        $routeWithoutClosingOptionals = rtrim($route, ']');
        $numOptionals = strlen($route) - strlen($routeWithoutClosingOptionals);

        // Split on [ while skipping placeholders
        $segments = preg_split('~' . self::VARIABLE_REGEX . '(*SKIP)(*F) | \[~x', $routeWithoutClosingOptionals);

        if ($numOptionals !== count($segments) - 1) {
            // If there are any ] in the middle of the route, throw a more specific error message
            if (preg_match('~' . self::VARIABLE_REGEX . '(*SKIP)(*F) | \]~x', $routeWithoutClosingOptionals)) {
                throw new BadRouteException('Optional segments can only occur at the end of a route');
            }
            throw new BadRouteException("Number of opening '[' and closing ']' does not match");
        }

        $routeDatas = [];

        foreach ($segments as $n => $segment) {
            if ($segment === '' && $n !== 0) {
                throw new BadRouteException('Empty optional part');
            }

            $routeDatas[] = $this->parsePlaceholders($segment);

        }

        return $routeDatas;
    }

    /**
     * Parses a route string that does not contain optional segments.
     *
     * @param string
     * @return mixed[]
     */
    private function parsePlaceholders($route)
    {
        if (!preg_match_all(
            '~' . self::VARIABLE_REGEX . '~x', $route, $matches,
            PREG_OFFSET_CAPTURE | PREG_SET_ORDER
        )) {
            return [$route];
        }

        $offset = 0;
        $routeData = [];
        foreach ($matches as $set) {
            if ($set[0][1] > $offset) {
                $routeData[] = substr($route, $offset, $set[0][1] - $offset);
            }
            $routeData[] = [
                $set[1][0],
                isset($set[2]) ? trim($set[2][0]) : self::DEFAULT_DISPATCH_REGEX
            ];
            $offset = $set[0][1] + strlen($set[0][0]);
        }

        if ($offset !== strlen($route)) {
            $routeData[] = substr($route, $offset);
        }

        return $routeData;
    }

    /**
     * @param array $reverseDatas
     *
     * @return ReverseRouter New instance of ReverseRouter
     */
    public static function getReverseRouter(array $reverseDatas): ReverseRouter
    {
        return new ReverseRouter(self::getReverseFunction(), $reverseDatas);
    }

    /**
     * @return callable
     */
    public static function getReverseFunction(): callable
    {
        return
        function (array $reverse, ?array ...$params): string 
        {
            //if (!$reverse) { throw new RuntimeException('Bad argument type.'); }
            $vars = [];
            $literal = true;

            if ($params) {
                foreach ($params as $n => $mixed) {
                    if (\is_array($mixed)) {
                        if (isset($mixed['literal'])) {
                            $literal = (bool) $mixed['literal'];
                            unset($mixed['literal']);
                        }

                        foreach ($mixed as $n => $var) {
                            if (\is_string($n)) {
                                $vars[trim($n)] = trim((string) $var);
                            } else {
                                $vars[] = trim((string) $var);
                            }
                        }
                    } elseif ($var = trim((string) $mixed)) {
                        $vars[] = $var;
                    } else {
                        throw new RuntimeException('Bad argument type.');
                    }
                }
            }

            $url = '';
            $paramIdx = 0;
            $varsCount = \count($vars);
            $replacements = [];

            foreach ($reverse as $part) {
                if ($paramIdx > $varsCount) {
                    throw new LogicException('Not enough parameters given');
                }

                $url .= $part[0];

                foreach ($part[1] as $var) {
                    if (isset($var[2])) {
                        if ($literal && $var[2] = 'literal') {
                            $replacements[] = $var[1];
                            continue;
                        }
                    }

                    if (isset($vars[$var[0]])) { // named var
                        $replacements[] = $vars[$var[0]];
                        unset($vars[$var[0]]);
                        --$varsCount;
                        continue;
                    } elseif (isset($vars[$paramIdx])) {
                        $replacements[] = $vars[$paramIdx];
                        unset($vars[$paramIdx]);
                    } else {
                        throw new LogicException('Not enough parameters given');
                    }

                    ++$paramIdx;
                }

                if ($paramIdx === $varsCount) {
                    break;
                }
            }

            if (!empty($vars)) {
                throw new LogicException('To much parameters given');
            }

            return vsprintf($url, $replacements);
        };
    }

    /**
     * @param array $routeData
     *
     * @return array
     */
    public function parseReverse(array $routeData): array
    {
        $reverse = [];

        foreach ($routeData as $k => $route) {
            $url = '';
            $vars = [];

            foreach ($route as $part) {
                if (\is_string($part)) {
                    $url .= $part;
                    continue;
                }
                // check if literal var
                if (preg_match('~^L_(.*)$~', $part[0])) {
                    $url .= '%s';
                    $vars[] = [$part[0], $part[1], 'literal'];
                } else {
                    $url .= '%s';
                    $vars[] = $part;
                }
            }

            $reverse[] = [$url, $vars];
        }

        return $reverse;
    }
}
