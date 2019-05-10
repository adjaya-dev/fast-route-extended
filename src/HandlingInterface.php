<?php

declare(strict_types=1);

namespace Adjaya\FastRoute;

interface HandlingInterface 
{
    public function __call($method, $parameters): HandlingInterface;

    public static function __callStatic($method, $parameters): \BadMethodCallException;

    public function add(array $addons): HandlingInterface;
}