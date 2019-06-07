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

    public function setDecorators(array $decorators) 
    {
        foreach ($decorators as $decorator) {
            $class = key($decorator);
            $options = (array) current($decorator);

            $this->routeCollectorDecorators[$class] = $options;
        }
        
        return $this;
    }

    public function create() 
    {
        $RouteCollector = 
            new $this->options['routeCollector']($this->getRouteParser(), $this->getDataGenerator());

        if (isset($this->routeCollectorDecorators)) {
            foreach ($this->routeCollectorDecorators as $class => $options) {
                $RouteCollector = new $class($RouteCollector, $options);
            }
        }

        return $RouteCollector;
    } 

    protected function getDataGenerator() 
    {
        $DataGenerator = new $this->options['dataGenerator']();

        if (isset($options['allowIdenticalRegexRoutes']) && !$options['allowIdenticalRegexRoutes']) 
        { // Default true
            $DataGenerator->allowIdenticalsRegexRoutes(false);
        }

        return $DataGenerator;
    }

    protected function getRouteParser() 
    {
        return new $this->options['routeParser']();
    }
    
}
