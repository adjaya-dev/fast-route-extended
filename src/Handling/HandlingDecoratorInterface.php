<?php

declare(strict_types=1);

namespace Adjaya\FastRoute\Handling;

use BadMethodCallException;

interface HandlingDecoratorInterface extends HandlingInterface
{
    public function __call(string $method, array $parameters);

    public static function __callStatic($method, $parameters): BadMethodCallException;

    public function getOriginalHandling(): HandlingInterface;
}
