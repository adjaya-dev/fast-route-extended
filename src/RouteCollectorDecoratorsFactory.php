<?php

declare(strict_types=1);

namespace Adjaya\FastRoute;

class RouteCollectorDecoratorsFactory implements RouteCollectorDecoratorsFactoryInterface
{
    protected $options = [];

    protected $routeCollectorDecoratorConfigurators;

    protected $routeCollector;

    public function __construct(array $routeCollectorDecoratorConfigurators = []) 
    {
        $this->setDecoratorConfigurators($routeCollectorDecoratorConfigurators);
    }

    public function setDecoratorConfigurators(array $decorators): RouteCollectorDecoratorsFactoryInterface
    {
        foreach ($decorators as $decorator) 
        {
            $this->setDecoratorConfigurator($decorator);
        }
        
        return $this;
    }

    public function setDecoratorConfigurator(RouteCollectorDecoratorConfiguratorInterface $decorator):  RouteCollectorDecoratorsFactoryInterface
    {
        $this->routeCollectorDecoratorConfigurators[] = $decorator;

        return $this;
    }

    public function decorate(RouteCollectorInterface $RouteCollector): RouteCollectorDecoratorInterface
    {
        foreach ($this->routeCollectorDecoratorConfigurators as $configurator) 
        {
            $RouteCollector = $configurator->decorate($RouteCollector);
        }

        return $RouteCollector;
    }
}
