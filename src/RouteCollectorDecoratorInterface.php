<?php

declare(strict_types=1);

namespace Adjaya\FastRoute;

interface RouteCollectorDecoratorInterface extends RouteCollectorInterface
{
    public function groupAddons(
        callable $callback, self $collector = null
    ): HandlingGroupInterface;
}
