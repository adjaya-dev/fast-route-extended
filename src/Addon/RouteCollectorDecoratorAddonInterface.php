<?php

declare(strict_types=1);

namespace Adjaya\FastRoute\Addon;

use Adjaya\FastRoute\Handling\HandlingInterface;

interface RouteCollectorDecoratorAddonInterface
{
    public function getData(): array;

    public function groupAddons(
        callable $callback, RouteCollectorDecoratorAddonInterface $collector = null): HandlingInterface;

    public function addGroup($prefix, callable $callback, 
        RouteCollectorDecoratorAddonInterface $collector = null): HandlingInterface;

    public function addRoute($httpMethod, $route, $handler): HandlingInterface;

    public function get($route, $handler): HandlingInterface;

    public function post($route, $handler): HandlingInterface;

    public function put($route, $handler): HandlingInterface;

    public function delete($route, $handler): HandlingInterface;

    public function patch($route, $handler): HandlingInterface;

    public function head($route, $handler): HandlingInterface;

    public function any($route, $handler): HandlingInterface;
}