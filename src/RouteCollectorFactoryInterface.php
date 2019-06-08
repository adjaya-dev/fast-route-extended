<?php

declare(strict_types=1);

namespace Adjaya\FastRoute;

interface RouteCollectorFactoryInterface
{
    public function setDecorators(array $decorators): RouteCollectorFactoryInterface;

    /**
     * Return RouteCollectorInterface || RouteCollectorDecoratorInterface
     */
    public function create(): Object;     
}