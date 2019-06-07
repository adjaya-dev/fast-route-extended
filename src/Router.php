<?php

declare(strict_types=1);

namespace Adjaya\FastRoute;

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
     * @var object
     */
    protected $dispatcher;

    /**
     * @var callable
     */
    protected $routeDefinitionCallback;

    protected $routeCollectorFactory;

    protected $options = [
        'dispatcher'       => Dispatcher\MarkBased::class,
        'cacheDisabled'    => false,
    ];

    /**
     * @param callable $routeDefinitionCallback
     * @param array    $options
     */
    public function __construct(
        callable $routeDefinitionCallback,
        $routeCollectorFactory,
        $options = []
    )
    {
        $this->routeDefinitionCallback = $routeDefinitionCallback;

        $this->routeCollectorFactory = $routeCollectorFactory;

        $this->options = $options + $this->options;
    }

    public function getCachedRouterDispatcher(Psr\Http\Message\RequestInterface $Request) 
    {
        $this->setCachedDispatcher();
        return $this->getRouterDispatcher($Request);
    }

    public function getSimpleRouterDispatcher(Psr\Http\Message\RequestInterface $Request) 
    {
        $this->setSimpleDispatcher();
        return $this->getRouterDispatcher($Request);
    }

    Protected function getRouterDispatcher(Psr\Http\Message\RequestInterface $Request) 
    {
        return new RouterDispatcher($this->Dispatcher, $Request);
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
     * @return object [return description]
     */
    public function getReverseRouter(): object
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

        $this->setDispatcher($dispatchData);
    }

    /**
     * Set cached Dispatcher.
     */
    public function setCachedDispatcher(): void
    {
        $dispatchData = $this->cachedRoutes();

        $this->setDispatcher($dispatchData);
    }

    protected function setDispatcher(array $dispatchData): void
    {
        $this->setRoutesData($dispatchData);

        $this->dispatcher = new $this->options['dispatcher']($dispatchData, $this->routesData);
    }

    public function simpleRoutes(): array
    {
        $routeCollector = $this->routeCollectorFactory->create();

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
