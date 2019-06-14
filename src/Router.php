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

    /**
     * @var DispatcherInterface
     */
    protected $dispatcher;

    /**
     * @var callable
     */
    protected $routeDefinitionCallback;

    protected $routeCollectorDecoratorConfigurators = [];
    
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

        $this->options =  $options + $this->options;
        /*/
        var_dump('************Router Options');
        echo '<pre>';
        print_r($this->options);
        echo '</pre>';
        //*/
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

    public function simpleRoutes(): array
    {
        $routeCollector = $this->getRouteCollector();

        if (isset($this->options['routeCollectorDecorators']) && $this->options['routeCollectorDecorators'])
        {
            $routeCollector = $this->getDecoratedRouteCollector($routeCollector);
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
        
    protected function getDispatcher(array $dispatchData): Dispatcher\DispatcherInterface
    {
        $this->setRoutesData($dispatchData);

        return new $this->options['dispatcher']($dispatchData, $this->routesData);
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

    protected function getDecoratedRouteCollector(
        RouteCollectorInterface $routeCollector
    ): RouteCollectorDecoratorInterface 
    {
        return $this->getRouteCollectorDecoratorsFactory()->create($routeCollector);
    }

    protected function getRouteCollectorDecoratorsFactory(): RouteCollectorDecoratorsFactoryInterface
    {
        return 
            new $this->options['routeCollectorDecoratorsFactory'](
                $this->getRouteCollectorDecoratorConfigurators()
            );
    }

    protected function getRouteCollectorDecoratorConfigurators(): array
    {
        $configurators = [];
        foreach ($this->options['routeCollectorDecorators'] as $configuratorClass => $options) {
            $configurators[] = $this->getConfigurator($configuratorClass, $options);
        }

        return $configurators;
    }

    protected function getConfigurator(string $configuratorClass, array $options): ConfiguratorInterface
    {
        return new $configuratorClass($options);
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

    public function getRoutesInfo() {
        return $this->routesData['info'];
    }

    /**
     * @param string $id Route id
     *
     * @return array|null
     */
    public function getRouteInfo(string $id): ?array
    {
        return !isset($this->routesData['info'][$id]) ? null : $this->routesData['info'][$id];
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
}
