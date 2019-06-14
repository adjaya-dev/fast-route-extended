<?php

declare(strict_types=1);

namespace Adjaya\FastRoute;

interface RouteCollectorDecoratorsFactoryInterface
{
    public function setDecoratorConfigurators(array $decorators): RouteCollectorDecoratorsFactoryInterface;

    public function setDecoratorConfigurator(ConfiguratorInterface $decorator):  RouteCollectorDecoratorsFactoryInterface;

    /**
     * Return RouteCollectorInterface || RouteCollectorDecoratorInterface
     */
    public function create(RouteCollectorInterface $RouteCollector): Object;     
}