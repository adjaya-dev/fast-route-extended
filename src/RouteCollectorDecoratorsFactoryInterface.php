<?php

declare(strict_types=1);

namespace Adjaya\FastRoute;

interface RouteCollectorDecoratorsFactoryInterface
{
    public function setDecoratorConfigurators(array $decorators): RouteCollectorDecoratorsFactoryInterface;

    public function setDecoratorConfigurator(RouteCollectorDecoratorConfiguratorInterface $decorator):  RouteCollectorDecoratorsFactoryInterface;

    /**
     * Return RouteCollectorInterface || RouteCollectorDecoratorInterface
     */
    public function decorate(RouteCollectorInterface $RouteCollector): RouteCollectorDecoratorInterface;
}
