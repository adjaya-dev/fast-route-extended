<?php

declare(strict_types=1);

namespace Adjaya\FastRoute\Addon;

class AddonFactory
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
     *    ]
     *
     * @var array
     */
    protected $addons;

    public function __construct(array $addons) 
    {
        $this->addons = $addons;
    }

    public function setHandlingProvider($handlingProviderClass) 
    {
        $this->handlingProviderClass = $handlingProviderClass;

        return $this;
    }

    public function setHandlingProviderDecorators($decorators) 
    {
        foreach ($decorators as $decorator) {
            $class = key($decorator);
            $params = (array) current($decorator);

            $this->handlingProviderDecorators[$class] = $params; 
        }

        return $this;
    }

    public function create() 
    {
        $options['addons'] = $this->addons;

        if ($this->handlingProviderClass) {
            $options['handlingProvider'] = $this->handlingProviderClass;
        }

        if ($this->handlingProviderDecorators) {
            $options['handlingProviderDecorators'] = $this->handlingProviderDecorators;
        }

        return [$this->addonClass => $options];
    }
}