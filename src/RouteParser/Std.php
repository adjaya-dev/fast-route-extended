<?php

declare(strict_types=1);

namespace Adjaya\FastRoute\RouteParser;

use FastRoute\RouteParser\Std as RouteParserStd;
use Adjaya\FastRoute\ReverseRouter;
use LogicException;
use RuntimeException;

class Std extends RouteParserStd
{
    /**
     * @param array $reverseDatas
     *
     * @return object New instance of ReverseRouter
     */
    public static function getReverseRouter(array $reverseDatas): object
    {
        return new ReverseRouter(self::getReverseFunction(), $reverseDatas);
    }

    /**
     * @return callable
     */
    public static function getReverseFunction(): callable
    {
        return
        function (array $reverse, ...$params): string 
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
        $prevVarsCount = 0;
        $prevUrl = '';

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

            if ($k >= 1) {
                $prevVarsCount += \count($reverse[$k - 1][1]);

                $varsCount = \count($vars) - $prevVarsCount;

                $_vars = [];

                for ($i = $varsCount - 1; $i >= 0; --$i) {
                    $_vars[$i] = array_pop($vars);
                }
                ksort($_vars);
                $vars = $_vars;

                $prevUrl .= $reverse[$k - 1][0];
                $url = preg_replace("~^($prevUrl)~", '', $url);
            }

            $reverse[] = [$url, $vars];
        }

        return $reverse;
    }
}
