<?php

declare(strict_types=1);

namespace Adjaya\FastRoute;

interface RouteCollectorDecoratorsFactoryInterface
{
    public function setDecoratorConfigurators(array $decorators): self;

    public function setDecoratorConfigurator(
        RouteCollectorDecoratorConfiguratorInterface $decorator
    ): self;

    /**
     * Return RouteCollectorInterface || RouteCollectorDecoratorInterface.
     */
    public function decorate(RouteCollectorInterface $RouteCollector): RouteCollectorDecoratorInterface;
}
