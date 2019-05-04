<?php

declare(strict_types=1);

namespace Adjaya\FastRoute;

class HandlingProviderDecoratorMacroable extends HandlingProviderDecoratorBase
{
    protected $RouteHandlingDecoratorMacro;

    protected $GroupHandlingDecoratorMacro;

    public function __construct(HandlingProviderInterface $HandlingProvider, array $options)
    {
        $this->HandlingProvider = $HandlingProvider;
        
        $this->RouteHandlingDecoratorMacro = get_class($this->getHandlingDecoratorClass());
            $this->HandlingProvider->setRouteHandlingDecorator($this->RouteHandlingDecoratorMacro);

        $this->GroupHandlingDecoratorMacro = get_class($this->getHandlingDecoratorClass());
            $this->HandlingProvider->setGroupHandlingDecorator($this->GroupHandlingDecoratorMacro);

        $this->builtAddonsMacros($this->getRegisteredAddons());

        $this->setOptions($options);
    }

    protected function getHandlingDecoratorClass(): HandlingDecoratorInterface
    {
        return new class(new Handling()) extends HandlingDecoratorMacro {};
    }

    protected function setOptions(array $options): void
    {
        if (isset($options['macros']) && !empty($options['macros'])) 
        {
            $this->setMacroables($options['macros']);
        }

        if (isset($options['addons']) && !empty($options['addons'])) 
        {
            $this->HandlingProvider->registerAddons($options['addons']);
        }
    }

    /**
     * @param array  $macroables
     */
    protected function setMacroables(array $macroables): void
    {
        foreach ($macroables as $scope => $macros) 
        {
            foreach ($macros as $name => $m) 
            {
                if (\is_callable($m)) {
                    if ('global' === $scope || 'route' === $scope) {
                        $this->RouteHandlingDecoratorMacro::macro($name, $m);
                    } 

                    if ('global' === $scope || 'group' === $scope) {
                        $this->GroupHandlingDecoratorMacro::macro($name, $m);
                    }

                    continue;
                }
                
                if (!\is_object($m)) {
                    if (class_exists($m)) {
                        $m = new $m();
                    } else {
                        throw new Exception("Class $m not Found!");
                    }
                }

                if (\is_object($m)) {
                    if ('global' === $scope || 'route' === $scope) {
                        $this->RouteHandlingDecoratorMacro::mixin($m);
                    }

                    if ('global' === $scope || 'group' === $scope) {
                        $this->GroupHandlingDecoratorMacro::mixin($m);
                    }
                }
            }
        }
    }

    /**
     * Built chainable macros.
     * 
     * @param   array  $_addons
     */
    protected function builtAddonsMacros(array $_addons): void 
    {
        foreach ($_addons as $scope => $addons) 
        {
            if (!isset($this->getRegisteredAddons()[$scope])) {
                $s = implode (' or ',  array_keys($this->getRegisteredAddons()));
                throw new Exception("$scope is not allowed. Allowed : $s.");
            }

            foreach ($addons as $k => $v) 
            {
                if ($k === $v) {
                    $callable = function ($args) use ($k) {
                        $this->add([$k => $args]);

                        //return $this;
                    };
                } else {
                    $callable = function ($addon, $args) use ($k) {
                        $this->add([$k => [$addon => $args]]);

                        //return $this;
                    };
                }

                if ($scope === 'route') {
                    $this->RouteHandlingDecoratorMacro::macro($k, $callable);
                } elseif ($scope === 'group') {
                    $this->GroupHandlingDecoratorMacro::macro($k, $callable);
                }
            }
        }
    }
}