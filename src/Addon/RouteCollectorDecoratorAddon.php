<?php

declare(strict_types=1);

namespace Adjaya\FastRoute\Addon;

use Adjaya\FastRoute\HandlingGroupInterface;
use Adjaya\FastRoute\HandlingRouteInterface;
use Adjaya\FastRoute\Handling\HandlingProvider;
use Adjaya\FastRoute\Handling\HandlingProviderDecoratorInterface;
use Adjaya\FastRoute\Handling\HandlingProviderInterface;
use Adjaya\FastRoute\RouteCollectorDecoratorInterface;
use Adjaya\FastRoute\RouteCollectorInterface;
use Exception;
use Throwable;

class RouteCollectorDecoratorAddon extends RouteCollectorDecoratorAddonBase
{
    /**
     * @var HandlingProviderInterface instance
     */
    protected $HandlingProvider;

    //protected $previousGroupHandling;

    protected $GroupHandling;

    public function __construct(RouteCollectorInterface $RouteCollector, ?array $options = null)
    {
        $this->RouteCollector = $RouteCollector;

        if (!empty($options)) {
            $this->setOptions($options);
        }

        $this->GroupHandling = $this->beforeAddGroup();
    }

    /**
     * Setting options.
     *
     * @param array $options
     */
    protected function setOptions(array $options): void
    {
        /*
        echo '<pre>';
        var_dump('********* $options *************');
        print_r($options);
        echo '</pre>';
        //*/

        if (isset($options['handlingProvider']) && !empty($options['handlingProvider'])) {
            $handlingProvider = $options['handlingProvider'];
        } else {
            $handlingProvider = HandlingProvider::class;
        }

        $this->HandlingProvider = $this->getHandlingProvider($handlingProvider, (array) $options);

        if (isset($options['handlingProviderDecorators'])) {
            foreach ($options['handlingProviderDecorators'] as $decorator => $options) {
                $this->HandlingProvider =
                    $this->getDecoratedHandlingProvider($decorator, (array) $options);
            }
        }
    }

    protected function getHandlingProvider(string $class, array $options): HandlingProviderInterface
    {
        return new $class($options);
    }

    protected function getDecoratedHandlingProvider(string $class, array $options): HandlingProviderDecoratorInterface
    {
        return new $class($this->HandlingProvider, $options);
    }

    public function __call(string $method, array $params) : HandlingInterface
    {
        try {
            return call_user_func_array([$this->GroupHandling, $method], $params);
        } catch (Throwable $e) {
            throw new exception ($e->getMessage());
        }
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
     * {@inheritdoc}
     *
     * @param string|array $prefix
     *
     * @return HandlingGroupInterface new instance of HandlingGroup
     */
    public function addGroup(
        $prefix, callable $callback, RouteCollectorDecoratorInterface $collector = null
    ): HandlingGroupInterface
    {
        if (!$collector) {
            $collector = $this;
        }

        $previousGroupHandling = $this->GroupHandling;

        $this->GroupHandling = $GroupHandling = $this->beforeAddGroup();

        $this->RouteCollector->addGroup($prefix, $callback, $collector);

        $this->GroupHandling = $previousGroupHandling;

        return $this->afterAddGroup($GroupHandling);
    }

    protected function beforeAddGroup(): HandlingGroupInterface
    {
        return $this->HandlingProvider->beforeAddGroup();
    }

    protected function afterAddGroup(HandlingInterface $GroupHandling): HandlingGroupInterface
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
    public function addRoute($httpMethod, $route, $handler): HandlingRouteInterface
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
        return
        $this->HandlingProvider->afterAddRoute($RouteHandling, $this->RouteCollector->getCurrentRouteId());
    }
}
