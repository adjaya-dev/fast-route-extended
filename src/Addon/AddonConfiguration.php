<?php

declare(strict_types=1);

namespace Adjaya\FastRoute\Addon;

use Adjaya\FastRoute\Handling\HandlingProviderDecoratorConfigurationInterface;
use Exception;

class AddonConfiguration implements AddonConfigurationInterface
{
    protected $addonClass = RouteCollectorDecoratorAddon::class;

    protected $handlingProviderDecorators;

    /**
     * Default Adjaya\FastRoute\Handling\HandlingProvider::class
     */
    protected $handlingProviderClass;

    /**
     *  [
     *      'global' => [],
     *      'route'  => [],
     *      'group'  => [],
     *  ]
     *
     * @var array
     */
    protected $addons;

    public function __construct(array $addons) 
    {
        $this->addons = $addons;
    }

    public function setHandlingProvider(string $handlingProviderClass): AddonConfigurationInterface  
    {
        // TODO https://www.php.net/manual/fr/function.class-implements.php
        $this->handlingProviderClass = $handlingProviderClass;

        return $this;
    }

    public function addHandlingProviderDecorator(
        HandlingProviderDecoratorConfigurationInterface $decorator): AddonConfigurationInterface
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

    public function provide(): array
    {
        $options['addons'] = $this->addons;

        if ($this->handlingProviderClass) {
            $options['handlingProvider'] = $this->handlingProviderClass;
        }

        if ($this->handlingProviderDecorators) 
        {
            $options['handlingProviderDecorators'] = $this->handlingProviderDecorators;
        }

        return [$this->addonClass => $options];
    }
}