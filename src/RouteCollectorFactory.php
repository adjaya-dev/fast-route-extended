<?php

declare(strict_types=1);

namespace Adjaya\FastRoute;

class RouteCollectorFactory
{
    protected $options = [
        'routeCollector'   => RouteCollector::class,
        'routeParser'      => RouteParser\Std::class,
        'dataGenerator'    => DataGenerator\MarkBased::class,
    ];

    protected $allowIdenticalRegexRoutes = true;

    protected $routeCollectorDecorators;

    public function __construct(?array $options = null) 
    {
        if ($options) {
            $this->options = $options + $this->options;
        }
    }

    public function setDecorators(array $decorators): RouteCollectorFactory
    {
        foreach ($decorators as $decorator) {
            $class = key($decorator);
            $options = (array) current($decorator);

            $this->routeCollectorDecorators[$class] = $options;
        }
        
        return $this;
    }

    public function create(): Object 
    {
        $RouteCollector = $this->getRouteCollector(); 

        if (isset($this->routeCollectorDecorators)) {
            $RouteCollector = $this->decorate($RouteCollector);
        }

        return $RouteCollector;
    }

    protected function getRouteCollector(): RouteCollectorInterface 
    {
        return new $this->options['routeCollector'](
                $this->getRouteParser(),
                $this->getDataGenerator()
            );
    }

    protected function Decorate($RouteCollector): RouteCollectorDecoratorInterface
    {
        foreach ($this->routeCollectorDecorators as $class => $options) {
            $RouteCollector = new $class($RouteCollector, $options);
        }

        return $RouteCollector;
    }

    protected function getDataGenerator(): DataGenerator\DataGeneratorInterface
    {
        $DataGenerator = new $this->options['dataGenerator']();

        if (isset($options['allowIdenticalRegexRoutes']) && !$options['allowIdenticalRegexRoutes']) 
        { // Default true
            $DataGenerator->allowIdenticalsRegexRoutes(false);
        }

        return $DataGenerator;
    }

    protected function getRouteParser(): RouteParser\RouteParserInterface  
    {
        return new $this->options['routeParser']();
    }
    
}
