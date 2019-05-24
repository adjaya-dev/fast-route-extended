<?php

declare(strict_types=1);

namespace Adjaya\FastRoute;

interface RouteCollectorInterface
{
    public function getData(): array;

    public function addGroup($prefix, callable $callback, RouteCollectorDecoratorAddonInterface $collector = null);

    public function addRoute($httpMethod, $route, $handler);

    public function get($route, $handler);

    public function post($route, $handler);

    public function put($route, $handler);

    public function delete($route, $handler);

    public function patch($route, $handler);

    public function head($route, $handler);

    public function any($route, $handler);

    public function getCurrentRouteId(): string;
}
