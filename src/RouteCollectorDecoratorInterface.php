<?php

declare(strict_types=1);

namespace Adjaya\FastRoute;

use Adjaya\FastRoute\Handling\HandlingInterface;

interface RouteCollectorDecoratorInterface
{
    public function getData(): array;

    public function groupAddons(
        callable $callback,
        RouteCollectorDecoratorInterface $collector = null
    ): HandlingInterface;

    public function addGroup(
        $prefix,
        callable $callback,
        RouteCollectorDecoratorInterface $collector = null
    ): HandlingInterface;

    public function addRoute($httpMethod, $route, $handler): HandlingInterface;

    public function get($route, $handler): HandlingInterface;

    public function post($route, $handler): HandlingInterface;

    public function put($route, $handler): HandlingInterface;

    public function delete($route, $handler): HandlingInterface;

    public function patch($route, $handler): HandlingInterface;

    public function head($route, $handler): HandlingInterface;

    public function any($route, $handler): HandlingInterface;
}
