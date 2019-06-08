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

    /**
     * @var callable
     */
    protected $routeCollectorFactory;

    /**
     * Router options
     *
     * @var array
     */
    protected $options = [
        'dispatcher'       => Dispatcher\MarkBased::class,
        'cacheDisabled'    => false,
    ];

    /**
     * @param callable $routeDefinitionCallback
     * @param callable $routeCollectorFactory
     * @param array    $options
     */
    public function __construct(
        callable $routeDefinitionCallback,
        callable $routeCollectorFactory,
        array $options = []
    )
    {
        $this->routeDefinitionCallback = $routeDefinitionCallback;

        $this->routeCollectorFactory = $routeCollectorFactory;

        $this->options = $options + $this->options;
    }

    /**
     * Return instance of RouteCollectorInterface
     * or RouteCollectorDecoratorInterface
     *
     * @param   callable  $routeCollectorFactory
     *
     * @return  object
     */
    protected function getRouteCollector(callable $routeCollectorFactory): object 
    {
        if ($routeCollectorFactory instanceof Closure) 
        {
            $routeCollector = $routeCollectorFactory();
        } 
        elseif (is_array($routeCollectorFactory) )
        {
            $routeCollector = call_user_func($routeCollectorFactory);
        }

        if ($routeCollector instanceof RouteCollectorFactoryInterface) {
            $routeCollector = $routeCollector->create();
        }

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
        $routeCollector = $this->getRouteCollector($this->routeCollectorFactory);

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
