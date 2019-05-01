<?php

declare(strict_types=1);

namespace Adjaya\FastRoute;

class HandlingProviderDecoratorMacroable extends HandlingProviderDecoratorBase
{
    public function __construct(HandlingProvider $HandlingProvider, array $options)
    {
        $this->HandlingProvider = $HandlingProvider;

        $this->setOptions($options);
    }

    protected function setOptions(array $options): void
    {
        if (isset($options['macros']) && !empty($options['macros'])) 
        {
            $this->setMacroables($options['macros']);
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
                        $this->_Handling['route']::macro($name, $m);
                    } 
                    if ('global' === $scope || 'group' === $scope) {
                        $this->_Handling['group']::macro($name, $m);
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
                        $this->_Handling['route']::mixin($m);
                    }
                    if ('global' === $scope || 'group' === $scope) {
                        $this->_Handling['group']::mixin($m);
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
                    $callable = function ($args) use ($k): HandlingInterface {
                        $this->add([$k => $args]);

                        return $this;
                    };
                } else {
                    $callable = function ($addon, $args) use ($k): HandlingInterface {
                        $this->add([$k => [$addon => $args]]);

                        return $this;
                    };
                }

                if ($scope === 'route') {
                    $this->_Handling['route']::macro($k, $callable);
                } elseif ($scope === 'group') {
                    $this->_Handling['group']::macro($k, $callable);
                }
            }
        }
    }
}