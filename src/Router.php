<?php

declare(strict_types=1);

namespace Adjaya\FastRoute;

use Closure;
use Exception;
use LogicException;
use RuntimeException;

class Router
{
    /**
     * @var array|null
     */
    protected $routesData;

    //protected $RouterDispatcher;

    /**
     * @var DispatcherInterface
     */
    protected $dispatcher;

    /**
     * @var callable
     */
    protected $routeDefinitionCallback;

    protected $routeCollectorDecoratorsStack;

    /**
     * @var callable
     */
    protected $routeCollectorDecoratorsFactory;

    /**
     * Router options
     *
     * @var array
     */
    protected $options = [
        'routeCollector'   => RouteCollector::class,
        'routeParser'      => RouteParser\Std::class,
        'dataGenerator'    => DataGenerator\MarkBased::class,
        'dispatcher'       => Dispatcher\MarkBased::class,
        'cacheDisabled'    => false,
        'routeCollectorDecoratorsFactory' => RouteCollectorDecoratorsFactory::class,
    ];

    /**
     * @param callable $routeDefinitionCallback
     * @param null|array    $options
     */
    public function __construct(callable $routeDefinitionCallback, ?array $options = [])
    {
        $this->routeDefinitionCallback = $routeDefinitionCallback;

        $this->options = $options + $this->options;
    }

    public function setRouteCollectorDecorators($decorators) 
    {
        $this->getRouteCollectorDecoratorsFactory()->setDecorators($decorators);
    }

    protected function getRouteCollectorDecoratorsFactory() 
    {
        if (!$this->routeCollectorDecoratorsFactory) {
            $this->routeCollectorDecoratorsFactory = new $this->options['routeCollectorDecoratorsFactory']() ;
        }

        return $this->routeCollectorDecoratorsFactory;
    }

    protected function getdecoratedRouteCollector(RouteCollectorInterface $routeCollector) 
    {
        return $this->getRouteCollectorDecoratorsFactory()->create($routeCollector);
    }

    /**
     * Return instance of RouteCollectorInterface
     * or RouteCollectorDecoratorInterface
     *
     * @param   callable  $routeCollectorFactory
     *
     * @return  object
     */
    protected function _getDecoratedRouteCollector(
        $routeCollector,
        callable $routeCollectorDecoratorsFactory): object 
    {
        if ($routeCollectorDecoratorsFactory instanceof Closure) 
        {
            $routeCollectorDecoratorsFactory = $routeCollectorDecoratorsFactory();
        } 
        elseif (is_array($routeCollectorDecoratorsFactory) )
        {
            $routeCollectorDecoratorsFactory = call_user_func($routeCollectorDecoratorsFactory);
        }

        if (!($routeCollectorDecoratorsFactory instanceof RouteCollectorDecoratorsFactoryInterface)) {
            $class = get_class($routeCollectorDecoratorsFactory);
            throw new Exception(
                "value of Adjaya\FastRoute\Router::getRouteCollector() 
                must be an instance of Adjaya\FastRoute\RouteCollectorDecoratorsFactoryInterface,
                instance of $class given"
            );
        }

        $routeCollector = 
            $routeCollectorDecoratorsFactory->create($routeCollector);

        if (
            $routeCollector instanceof RouteCollectorInterface || 
            $routeCollector instanceof RouteCollectorDecoratorInterface
        ) {
            return $routeCollector;
        } else {
            $class = get_class($routeCollector);
            throw new Exception(
                "Return value of Adjaya\FastRoute\Router::getRouteCollector() 
                must be an instance of Adjaya\FastRoute\RouteCollectorInterface 
                or Adjaya\FastRoute\RouteCollectorDecoratorInterface, 
                instance of $class returned"
            );
        }
    }

    protected function getRouteCollector(): RouteCollectorInterface 
    {
        return new $this->options['routeCollector'](
                $this->getRouteParser(),
                $this->getDataGenerator()
            );
    }

    protected function getRouteParser(): RouteParser\RouteParserInterface  
    {
        return new $this->options['routeParser']();
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

    /**
     * @param string $method
     * @param string $path
     *
     * @return array $routeInfo
     */
    public function dispatch(string $method, string $path): array
    {
        if ($this->dispatcher) {
            $routeInfo = $this->dispatcher->dispatch($method, $path);

            return $routeInfo;
        }
        throw new LogicException('Dispatcher must be set first');
    }

    /**
     * @param array &$dispatchData
     */
    protected function setRoutesData(array &$dispatchData): void
    {
        if (isset($dispatchData['routes_data'])) {
            $this->routesData = $dispatchData['routes_data'];
            unset($dispatchData['routes_data']);
        }
    }

    /**
     * Get routes Data.
     *
     * @return array
     */
    public function getRoutesData(): array
    {
        return $this->routesData;
    }

    /**
     * @param string $id Route id
     *
     * @return array|null
     */
    public function getRouteInfo(string $id): ?array
    {
        if ($routes_info = !isset($this->routesData['info'][$id]) ?
            null : $this->routesData['info'][$id]
        ) {
            return $routes_info;
        }
    }

    /**
     * @return array
     */
    public function getReverseRoutesData(): array
    {
        if (isset($this->routesData['reverse'])) {
            return $this->routesData['reverse'];
        }

        throw new Exception('Not reverse data found!');
    }

    /**
     * @return ReverseRouter
     */
    public function getReverseRouter(): ReverseRouter
    {
        if (method_exists($this->options['routeParser'], 'getReverseRouter')) 
        {
            return 
            ($this->options['routeParser'])::getReverseRouter($this->getReverseRoutesData());
        }

        throw new RuntimeException($this->options['routeParser'].'::getReverseRouter does not exist');
    }

    /**
     * Set simple Dispatcher.
     */
    public function setSimpleDispatcher(): void
    {
        $dispatchData = $this->simpleRoutes();

        $this->dispatcher = $this->getDispatcher($dispatchData);
    }

    /**
     * Set cached Dispatcher.
     */
    public function setCachedDispatcher(): void
    {
        $dispatchData = $this->cachedRoutes();

        $this->dispatcher = $this->getDispatcher($dispatchData);
    }

    protected function getDispatcher(array $dispatchData): Dispatcher\DispatcherInterface
    {
        $this->setRoutesData($dispatchData);

        return new $this->options['dispatcher']($dispatchData, $this->routesData);
    }

    public function simpleRoutes(): array
    {
        $routeCollector = $this->getRouteCollector();

        if ($this->routeCollectorDecoratorsFactory) {
            $routeCollector = 
                $this->getDecoratedRouteCollector(
                    $routeCollector,
                    $this->routeCollectorDecoratorsFactory
                );
        }
 
        $routeDefinitionCallback = $this->routeDefinitionCallback;
        $routeDefinitionCallback($routeCollector);

        return $routeCollector->getData();
    }

    public function cachedRoutes(): array
    {
        $options = $this->options;

        if (!isset($options['cacheFile'])) {
            throw new LogicException('Must specify "cacheFile" option');
        }

        if (!$options['cacheDisabled'] && file_exists($options['cacheFile'])) {
            $dispatchData = require $options['cacheFile'];
            if (!\is_array($dispatchData)) {
                throw new RuntimeException('Invalid cache file "' . $options['cacheFile'] . '"');
            }

            return $dispatchData;
        }

        $dispatchData = $this->simpleRoutes();

        file_put_contents(
            $options['cacheFile'],
            '<?php return ' . var_export($dispatchData, true) . ';'
        );

        return $dispatchData;
    }
}
