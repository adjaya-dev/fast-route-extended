<?php

declare(strict_types=1);

namespace Adjaya\FastRoute;

interface RouteCollectorInterface
{
    public function getData(): array;

    public function addGroup($prefix, callable $callback, CollectorInterface $collector): HandlingGroupInterface;

    public function addRoute($httpMethod, $route, $handler): HandlingRouteInterface;

    //public function getCurrentRouteId(): string;
}
