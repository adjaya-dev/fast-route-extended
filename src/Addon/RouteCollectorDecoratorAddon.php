<?php

declare(strict_types=1);

namespace Adjaya\FastRoute\Addon;

use Adjaya\FastRoute\Handling\HandlingInterface;
use Adjaya\FastRoute\Handling\HandlingProvider;
use Adjaya\FastRoute\Handling\HandlingProviderInterface;
use Adjaya\FastRoute\RouteCollectorDecoratorInterface;
use Adjaya\FastRoute\RouteCollectorInterface;
use Exception;

class RouteCollectorDecoratorAddon extends RouteCollectorDecoratorAddonBase
{
    /**
     * @var HandlingProviderInterface instance
     */
    protected $HandlingProvider;

    public function __construct(RouteCollectorInterface $RouteCollector, ?array $options = null)
    {
        $this->RouteCollector = $RouteCollector;

        if (!empty($options)) {
            $this->setOptions($options);
        }
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

        $this->setHandlingProvider($handlingProvider, $options);

        if (isset($options['handlingProviderDecorators'])) {
            foreach ($options['handlingProviderDecorators'] as $decorator => $decorator_options) {
                $this->setHandlingProviderDecorator($decorator, (array) $decorator_options);
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
                'HandlingProvider must be instance of HandlingProviderInterface'
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
     * {@inheritdoc}
     *
     * @param string|array $prefix
     *
     * @return HandlingInterface new instance of GroupHandling
     */
    public function addGroup(
        $prefix,
        callable $callback,
        RouteCollectorDecoratorInterface $collector = null
    ): HandlingInterface {
        if (!$collector) {
            $collector = $this;
        }

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
        return
        $this->HandlingProvider->afterAddRoute($RouteHandling, $this->RouteCollector->getCurrentRouteId());
    }
}
