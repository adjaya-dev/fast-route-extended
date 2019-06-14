<?php

declare(strict_types=1);

namespace Adjaya\FastRoute\Handling;

use Exception;

class HandlingProviderDecoratorMacroable extends HandlingProviderDecoratorBase
{
    public function __construct(HandlingProviderInterface $HandlingProvider, array $options)
    {
        $this->HandlingProvider = $HandlingProvider;

        $this->HandlingProvider->setRouteHandlingDecorator(RouteHandlingDecoratorMacro::class);

        $this->HandlingProvider->setGroupHandlingDecorator(GroupHandlingDecoratorMacro::class);

        $auto = true; // Default
        if (isset($options['auto'])) {
            if (!$options['auto']) {
                $auto = false;
            }

            unset($options['auto']);
        }

        if ($auto) {
            $this->builtAddonsMacros($this->getRegisteredAddons());
        }

        $this->setOptions($options);
    }

    protected function setOptions(array $macros): void
    {
        if (!empty($macros)) 
        {
            $this->setMacroables($macros);
        }
    }

    /**
     * @param array  $macroables
     */
    protected function setMacroables(array $macroables): void
    {
        foreach ($macroables as $scope => $macros) 
        {
            if (!in_array($scope, ['global', 'route', 'group'])) {
                throw new Exception("$scope not exists, must be 'global', 'route', or 'group'"); 
            }

            foreach ($macros as $name => $m) 
            {
                if (\is_callable($m)) 
                {
                    if (is_array($m)) {
                        $m = call_user_func_array($m);
                    }

                    $this->setMacro($scope, $name, $m);
                    continue;
                }

                if (\is_string($m) && class_exists($m)) {
                    $m = new $m();
                } else {
                    throw new Exception("Class $m not Found!");
                }

                if (\is_object($m)) {
                    $methods = (new \ReflectionClass($m))->getMethods(
                        \ReflectionMethod::IS_PUBLIC | \ReflectionMethod::IS_PROTECTED
                    );
            
                    foreach ($methods as $method) {
                        $method->setAccessible(true);

                        $this->setMacro($scope, $method->name, $method->invoke($m)); 
                    }
                }
            }
        }
    }

    protected function setMacro(string $scope, string $name, \Closure $macro): void 
    {
        if ('global' === $scope || 'route' === $scope) {
            RouteHandlingDecoratorMacro::macro($name, $macro);
        } 

        if ('global' === $scope || 'group' === $scope) {
            GroupHandlingDecoratorMacro::macro($name, $macro);
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

            foreach ($addons as $name => $v) 
            {
                if ($name === $v) {
                    $macro = function ($arg) use ($name) {
                        $this->add([$name => $arg]);
                    };
                } else {
                    $macro = function ($arg1, $arg2) use ($name) {
                        $this->add([$name => [$arg1 => $arg2]]);
                    };
                }

                $this->setMacro($scope, $name, $macro);
            }
        }
    }
}