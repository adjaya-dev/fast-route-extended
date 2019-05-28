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

    protected $options =
    [
        'router' => [],
        'routeParser' =>    \Adjaya\FastRoute\RouteParser\Std::class,
        'dataGenerator' =>  \Adjaya\FastRoute\DataGenerator\MarkBased::class,
        'dispatcher' =>     \Adjaya\FastRoute\Dispatcher\MarkBased::class,
        'routeCollector' => \Adjaya\FastRoute\RouteCollector::class,

        'handlingProvider' => \Adjaya\FastRoute\Handling\HandlingProvider::class,
        'addon' => \Adjaya\FastRoute\Addon\RouteCollectorDecoratorAddon::class,
        'macro' => \Adjaya\FastRoute\Handling\HandlingProviderDecoratorMacroable::class,

        'cacheDisabled'  => false,
    ];

    /**
     * @param callable $routeDefinitionCallback
     * @param array    $options
     */
    public function __construct(callable $routeDefinitionCallback, $options = [])
    {
        $this->routeDefinitionCallback = $routeDefinitionCallback;
        if (isset($options['router']))
        {
            $routerOptions = $this->setRouterOptions($options['router']);
            $this->options = $routerOptions + $this->options;
        } 
        // TODO
        //$this->options = $options + $this->options;
    }

    protected function setRouterOptions($options) 
    {
        $_options = [];

        if (isset($options['addons']) || isset($options['macros'])) {
            $_options['routeCollectorDecorators'][$this->options['addon']] = [
                'enabled' => true,
                'options' => [
                    'handlingProvider' =>
                    $this->options['handlingProvider'],
                ], 
            ];
        }

        if (isset($options['addons'])) {
            $_options['routeCollectorDecorators'][$this->options['addon']]['options']['addons'] = $options['addons'];
        }

        if (isset($options['macros'])) {
            $_options['routeCollectorDecorators'][$this->options['addon']]['options']['handlindProviderDecorators'] = [
                $this->options['macro'] => $options['macros'],
            ];
        }

        if (isset($options['cacheFile'])) {
            $_options['cacheFile'] = $options['cacheFile'];
        }

        if (isset($options['cacheDisabled'])) {
            $_options['cacheDisabled'] = $options['cacheDisabled'];
        }

        /*
        var_dump('******** ROUTER OPTIONS **********');
        echo '<pre>';
        print_r($_options);
        echo '</pre>';
        */

        return $_options;
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
            /*
            if (isset($routeInfo[1]) && \is_string($routeInfo[1])
                && $route = $this->getRouteInfo($routeInfo[1])) {
                $routeInfo[1] = $route;
            }
            */
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
    public function getReverseRouter(): ?object
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
        $options = $this->options;

        /** @var RouteCollector $routeCollector */
        $DataGenerator = new $options['dataGenerator']();

        if (isset($options['router']['allowIdenticalRegexRoutes']) && !$options['router']['allowIdenticalRegexRoutes']) {
            $DataGenerator->allowIdenticalsRegexRoutes(false);
        }

        $routeCollector = new $options['routeCollector'](
            new $options['routeParser'](), $DataGenerator
        );

        if (isset($options['routeCollectorDecorators'])) {
            foreach ($options['routeCollectorDecorators'] as $decorator => $v) {
                $v = (array) $v;
                if (isset($v['enabled']) && $v['enabled']) {
                    $_options = null;
                    if (isset($v['options'])) { 
                        $_options = $v['options']; 
                    }
                    $routeCollector = new $decorator($routeCollector, $_options);
                }
            }
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
