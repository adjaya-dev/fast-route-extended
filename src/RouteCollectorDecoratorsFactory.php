<?php

declare(strict_types=1);

namespace Adjaya\FastRoute;

class RouteCollectorDecoratorsFactory implements RouteCollectorDecoratorsFactoryInterface
{
    protected $options = [];

    protected $routeCollectorDecorators;

    public function __construct(?array $options = null) 
    {
        if ($options) {
            $this->options = $options + $this->options;
        }
    }

    public function setDecorators(array $decorators): RouteCollectorDecoratorsFactoryInterface
    {
        foreach ($decorators as $decorator) {

            $this->setDecorator($decorator);
        }
        
        return $this;
    }

    public function setDecorator(ConfigurationInterface $decorator):  RouteCollectorDecoratorsFactoryInterface
    {
        $decorator = $decorator->provide();

        $class = key($decorator);
        $options = (array) current($decorator);

        $this->routeCollectorDecorators[$class] = $options;

        return $this;
    }

    public function create(RouteCollectorInterface $RouteCollector): Object 
    {
        if (isset($this->routeCollectorDecorators)) {
            $RouteCollector = $this->decorate($RouteCollector);
        }

        return $RouteCollector;
    }

    protected function decorate(RouteCollectorInterface $RouteCollector): RouteCollectorDecoratorInterface
    {
        foreach ($this->routeCollectorDecorators as $class => $options) {
            $RouteCollector = new $class($RouteCollector, $options);
        }

        return $RouteCollector;
    }
}
