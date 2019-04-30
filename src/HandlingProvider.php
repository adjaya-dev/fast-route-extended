<?php

declare(strict_types=1);

namespace Adjaya\FastRoute;

use Exception;
use ReflectionMethod;

class HandlingProvider implements HandlingProviderInterface
{
    /**
     * @var array
     */
    protected $registeredAddons = ['route' => [], 'group' => []];

    /**
     * @var array
     */
    protected $routesAddonsData = [];

    /**
     * The Reference of the current element of $this->routesAddonsData.
     *
     * @var array
     */
    protected $routesAddonsDataCurrentIndex;

    /**
     * @var array
     */
    protected static $groupsAddonsDataPreviousIndex = [];

    /**
     * @var HandlingInterface RouteHandling instance
     */
    protected $RouteHandling;

    /**
     * @var HandlingInterface current GroupHandling instance
     */
    protected $GroupHandling;

    /**
     * Array of Anonymous class that extends Handling.
     *
     * @var array
     */
    protected $_Handling;

    /**
     * @var ReflectionMethod RouteHandling::setHandlers
     */
    protected $setRouteHandlers;

    /**
     * @var ReflectionMethod GroupHandling::setHandlers
     */
    protected $setGroupHandlers;

    public function __construct(?array $options = [])
    {
        $this->routesAddonsDataCurrentIndex = & $this->routesAddonsData;
        
        if (isset($options['handling'])) {
            $Handling = $options['handling'];
        } else {
            $Handling = Handling::class;
        }

        class_alias($Handling , __NAMESPACE__ . '\_Handling');

        $this->_Handling = [
            'route' => new class() extends _Handling {},
            'group' => new class() extends _Handling {},
        ];

        $this->setOptions($options);

        $this->builtAddonsMacros($this->registeredAddons);
    }

    /**
     * Setting options.
     *
     * @param array $options
     */
    protected function setOptions(array $options): void
    {
        if (isset($options['addons']) && !empty($options['addons'])) 
        {
            $this->registerAddons($options['addons']);
        }

        if (isset($options['macros']) && !empty($options['macros'])) 
        {
            $this->setMacroables($options['macros']);
        }
    }

    protected function registerAddons($__addons) 
    {
        foreach ($__addons as $scope => $addons) 
        {
            $_addons = [];

            foreach ((array) $addons as $key => $value) 
            {
                if (is_numeric($key)) {
                    foreach ((array) $value as $v) {
                        $_addons[$v] = $v;
                    }
                } else {
                    $_addons[$key] = $value;
                }
            }

            if ($scope === 'route' || $scope === 'global') {
                $this->registeredAddons['route'] = 
                    array_merge($this->registeredAddons['route'], $_addons);
            }

            if ($scope === 'group' || $scope = 'global') {
                $this->registeredAddons['group'] = 
                    array_merge($this->registeredAddons['group'], $_addons);
            }
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
            if (!isset($this->registeredAddons[$scope])) {
                $s = implode (' or ',  array_keys($this->registeredAddons));
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

    public function processAddons(array & $routesData): void
    {
        echo '*********processAddons***********<br>';
        echo '<pre>';
        print_r($this->routesAddonsData);
        echo '</pre>';
        $this->_processAddons($this->routesAddonsData, $routesData);
    }

    /**
     * Recursive function
     *
     * @param array      $addonsData
     * @param array|null &$result
     * @param array      $current_addons
     */
    protected function _processAddons(
        array $addonsData, ?array & $result, array $current_addons = []): void
    {
        foreach ($addonsData as $k => $v) {
            if ('*addons*' === $k) {
                if ($v) {
                    $current_addons[] = $v;
                }
            } elseif (\is_string($k)) { // route id
                if ($current_addons) {
                    $group_addons = $current_addons;
                }

                if ($v) {
                    $group_addons[] = $v;
                }

                if (isset($group_addons)) {
                    array_unshift($group_addons, $result[$k]);
                    $result[$k] = call_user_func_array('array_merge_recursive', $group_addons);

                    var_dump('********$result[$k]********');
                    echo '<pre>';
                    print_r($result[$k]);
                    echo '</pre>';
                }
            } else { // $v is numeric
                $this->_processAddons($v, $result, $current_addons);
            }
        }
    }

    /**
     * @return HandlingInterface $this->RouteHandling singleton instance
     */
    protected function getRouteHandling(): HandlingInterface
    {
        if (!$this->RouteHandling) {
            $this->setRouteHandlers =
                new ReflectionMethod($this->_Handling['route'], 'setHandlers');
            $this->setRouteHandlers->setAccessible(true);

            $this->RouteHandling =
                new $this->_Handling['route']($this->registeredAddons['route']);
        }

        return $this->RouteHandling;
    }

    /**
     * @return HandlingInterface new $this->GroupHandling instance
     */
    protected function getGroupHandling(): HandlingInterface
    {
        if (!$this->GroupHandling) {
            $this->setGroupHandlers =
                new ReflectionMethod($this->_Handling['group'], 'setHandlers');
            $this->setGroupHandlers->setAccessible(true);
        }

        $this->GroupHandling =
        new $this->_Handling['group']($this->registeredAddons['group']);

        return $this->GroupHandling;
    }

    /**
     * @return HandlingInterface
     */
    public function beforeAddRoute(): HandlingInterface
    {
        return $this->getRouteHandling();
    }

    /**
     * @param  HandlingInterface $RouteHandling
     * @param  string            $route_id The current route id
     *
     * @return HandlingInterface $RouteHandling
     */
    public function afterAddRoute(
        HandlingInterface $RouteHandling, string $route_id): HandlingInterface
    {
        $this->setRouteHandlers->invokeArgs(
            $RouteHandling,
            [
                & $this->routesAddonsDataCurrentIndex[$route_id],
                $route_id
            ]
        );

        return $RouteHandling;
    }

    /**
     * @return HandlingInterface $GroupHandling
     */
    public function beforeAddGroup(): HandlingInterface
    {
        $previousIdx = & $this->routesAddonsDataCurrentIndex;

        $this->routesAddonsDataCurrentIndex =
        & $this->routesAddonsDataCurrentIndex[];

        $GroupHandling = $this->getGroupHandling();

        $previousIndex = & $this->groupsAddonsDataPreviousIndex();
        $previousIndex[spl_object_id($GroupHandling)] = & $previousIdx;

        $this->setGroupHandlers->invokeArgs(
            $GroupHandling,
            [
                & $this->routesAddonsDataCurrentIndex['*addons*']
            ]
        );

        return $GroupHandling;
    }
    
    /**
     * @param  HandlingInterface $GroupHandling
     *
     * @return HandlingInterface $GroupHandling
     */
    public function afterAddGroup(HandlingInterface $GroupHandling): HandlingInterface
    {
        $this->routesAddonsDataCurrentIndex =
            & $this->groupsAddonsDataPreviousIndex()[spl_object_id($GroupHandling)];

        return $GroupHandling;
    }

    /**
     * @return  & array
     */
    protected function & groupsAddonsDataPreviousIndex() 
    {
        return self::$groupsAddonsDataPreviousIndex;
    }
}