<?php

declare(strict_types=1);

namespace Adjaya\FastRoute;

interface RouteCollectorDecoratorsFactoryInterface
{
    public function setDecorators(array $decorators): RouteCollectorDecoratorsFactoryInterface;

    /**
     * Return RouteCollectorInterface || RouteCollectorDecoratorInterface
     */
    public function create(RouteCollectorInterface $RouteCollector): Object;     
}