<?php

declare(strict_types=1);

namespace Adjaya\FastRoute;

use Exception;

class ReverseRouter
{
    /**
     * @var array
     */
    protected $reverseRoutesData;

    /**
     * @var callable
     */
    protected $reverseFunction;

    /**
     * @param callable $reverseFunction
     * @param array    $reverseRoutesData
     */
    public function __construct(callable $reverseFunction, array $reverseRoutesData)
    {
        $this->reverseFunction = $reverseFunction;
        $this->reverseRoutesData = $reverseRoutesData;
    }

    /**
     * @param string $id
     * @param array  $vars
     * @param bool   $literal
     *
     * @return string The formated route uri
     */
    public function route(string $name, ...$params): string
    {
        if (isset($this->reverseRoutesData[$name])) {
            $route = $this->reverseFunction;

            if ($params) {
                return $route($this->reverseRoutesData[$name], ...$params);
            }

            return $route($this->reverseRoutesData[$name]);
        }
        throw new Exception("The route name '$name' does not exists");
    }
}
