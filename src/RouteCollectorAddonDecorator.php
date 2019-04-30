<?php

declare(strict_types=1);

namespace Adjaya\FastRoute;

use Adjaya\FastRoute\RouteCollector;
use Exception;

class RouteCollectorAddonDecorator implements RouteCollectorDecoratorInterface
{
    /**
     * @var HandlingProvider instance
     */
    protected $HandlingProvider;

    /**
     * @var RouteCollector
     */
    protected $RouteCollector;
 
    public function __construct(RouteCollectorInterface $RouteCollector)
    {

        $this->RouteCollector = $RouteCollector;
    }

    /**
     * Setting options.
     *
     * @param array $options
     */
    protected function setOptions(array $options): void
    {
        if (isset($options['handlingProvider'])) {
            $handlingProvider = $options['handlingProvider'];
        } else {
            $handlingProvider = HandlingProvider::class;
        }

        $this->setHandlingProvider($handlingProvider, $options);

        if (isset($options['handlindProviderDecorators'])) {
            foreach ($options['handlindProviderDecorators'] as $decorator => $decorator_options) {
                $this->setHandlingProviderDecorator($decorator, $decorator_options);
            }
        }
    }

    protected function setHandlingProvider(string $handlingProvider, array $options): void 
    {
        if (!$this->HandlingProvider) {
            $this->validateHandlingProvider(new $handlingProvider($options));
        }
    }

    protected function setHandlingProviderDecorator(string $decorator, array $options) 
    {
        $this->validateHandlingProvider(new $decorator($this->HandlingProvider, $options));
    }

    protected function validateHandlingProvider($HandlingProvider) 
    {
        if (!($HandlingProvider instanceof HandlingProviderInterface)) {
            throw new Exception(
                "HandlingProvider must be instance of HandlingProviderInterface"
            );
        }

        $this->HandlingProvider = $HandlingProvider;

    }
    
    public function getData(): array
    {
        $routes_data = $this->RouteCollector->getData();
        
        if ($routes_data && isset($routes_data['routes_data']['info'])) {
            $this->HandlingProvider->processAddons($routes_data['routes_data']['info']);
        }

        return $routes_data;
    }

    /**
     * Create an addons group.
     * 
     * @param callable $callback
     */

    public function groupAddons(callable $callback,
        RouteCollectorDecoratorInterface $collector = null): HandlingInterface
    {
        if (!$collector) { $collector = $this; }

        return $this->addGroup('', $callback, $collector);
    }

    /**
     * {@inheritdoc}
     *
     * @param string|array $prefix
     *
     * @return HandlingInterface new instance of GroupHandling
     */
    public function addGroup($prefix, callable $callback,
        RouteCollectorDecoratorInterface $collector = null): HandlingInterface
    {
        if (!$collector) { $collector = $this; }

        $GroupHandling = $this->beforeAddGroup();

        $this->RouteCollector->addGroup($prefix, $callback, $collector);

        return $this->afterAddGroup($GroupHandling);
    }

    protected function beforeAddGroup(): HandlingInterface
    {
        return $this->HandlingProvider->beforeAddGroup();    
    }

    protected function afterAddGroup(HandlingInterface $GroupHandling): HandlingInterface
    {
        return $this->HandlingProvider->afterAddGroup($GroupHandling);    
    }

    /**
     * {@inheritdoc}
     *
     * @param string|array $route
     *
     * @return HandlingInterface singleton instance of RouteHandling
     */
    public function addRoute($httpMethod, $route, $handler): HandlingInterface
    {
        $RouteHandling = $this->beforeAddRoute();
        
        $this->RouteCollector->addRoute($httpMethod, $route, $handler);

        return $this->afterAddRoute($RouteHandling);
    }

    protected function beforeAddRoute(): HandlingInterface
    {
        return $this->HandlingProvider->beforeAddRoute();    
    }

    protected function afterAddRoute(HandlingInterface $RouteHandling): HandlingInterface
    {
        return $this->HandlingProvider->afterAddRoute($RouteHandling, $this->RouteCollector->getCurrentRouteId());    
    }

    public function getCurrentRouteId(): string 
    {
        return $this->RouteCollector->getCurrentRouteId();
    }

    public function get($route, $handler) : HandlingInterface {
        return $this->addRoute('GET', $route, $handler);
    }

    public function post($route, $handler): HandlingInterface {
        return $this->addRoute('POST', $route, $handler);
    }

    public function put($route, $handler): HandlingInterface {
        return $this->addRoute('PUT', $route, $handler);
    }

    public function delete($route, $handler): HandlingInterface {
        return $this->addRoute('DELETE', $route, $handler);
    }

    public function patch($route, $handler): HandlingInterface {
        return $this->addRoute('PATCH', $route,$handler);
    }

    public function head($route, $handler): HandlingInterface {
        return $this->addRoute('HEAD', $route, $handler);
    }   
}