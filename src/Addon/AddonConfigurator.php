<?php

declare(strict_types=1);

namespace Adjaya\FastRoute\Addon;

use Adjaya\FastRoute\Handling\HandlingProviderDecoratorConfiguratorInterface;
use Adjaya\FastRoute\RouteCollectorDecoratorInterface;
use Exception;

class AddonConfigurator implements AddonConfiguratorInterface
{
    protected $scopes = ['global', 'route', 'group'];

    protected $addonClass = RouteCollectorDecoratorAddon::class;

    protected $handlingProviderDecorators = [];

    /**
     * Default Adjaya\FastRoute\Handling\HandlingProvider::class.
     */
    protected $handlingProviderClass;

    /**
     *  [
     *      'global' => [],
     *      'route'  => [],
     *      'group'  => [],
     *  ].
     *
     * @var array
     */
    protected $addons;

    public function __construct(array $options)
    {
        foreach ($options['addons'] as $scope => $addons) {
            $this->addAddons($scope, $addons);
        }

        if (isset($options['handlingProviderDecorators']) && $options['handlingProviderDecorators']) {
            foreach ($options['handlingProviderDecorators'] as $decoratorClass => $options) {
                $this->addHandlingProviderDecorator(new $decoratorClass($options));
            }
        }

        if (isset($options['handlingProviderClass']) && $options['handlingProviderClass']) {
            $this->handlingProviderClass = $options['handlingProviderClass'];
        }
    }

    public function setHandlingProvider(string $handlingProviderClass): AddonConfiguratorInterface
    {
        // TODO https://www.php.net/manual/fr/function.class-implements.php
        $this->handlingProviderClass = $handlingProviderClass;

        return $this;
    }

    public function addAddons(string $scope, array $addons): AddonConfiguratorInterface
    {
        if (\in_array($scope, $this->scopes, true)) {
            $this->addons[$scope] = $addons;
        }

        return $this;
    }

    public function addHandlingProviderDecorator(
        HandlingProviderDecoratorConfiguratorInterface $decorator
    ): AddonConfiguratorInterface 
    {
        $decorator = $decorator->provide();

        $class = key($decorator);
        $params = (array) current($decorator);

        if (isset($this->handlingProviderDecorators[$class])) {
            throw new Exception("handlingProviderDecorator $class is already set");
        }

        $this->handlingProviderDecorators[$class] = $params;

        return $this;
    }

    public function decorate(object $routeCollector): RouteCollectorDecoratorInterface
    {
        return new $this->addonClass($routeCollector, $this->getOptions());
    }

    public function provide(): array
    {
        return [$this->addonClass => $this->getOptions()];
    }

    protected function getOptions(): array
    {
        $options['addons'] = $this->addons;

        if ($this->handlingProviderClass) {
            $options['handlingProvider'] = $this->handlingProviderClass;
        }

        if (!empty($this->handlingProviderDecorators)) {
            $options['handlingProviderDecorators'] = $this->handlingProviderDecorators;
        }

        return $options;
    }
}
