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

    protected $RouteHandlingDecorator = [];

    protected $GroupHandlingDecorator = [];

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
    }

    public function getRegisteredAddons(): array 
    {
        return $this->registeredAddons;
    }

    public function setRouteHandlingDecorator($routeHandlingDecorator): void
    {
        $this->RouteHandlingDecorator[] = $routeHandlingDecorator;
    }

    public function setGroupHandlingDecorator($groupHandlingDecorator): void
    {
        $this->GroupHandlingDecorator[] = $groupHandlingDecorator;
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
    }

    public function registerAddons(array $__addons): void 
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

            if ($scope === 'group' || $scope === 'global') {
                $this->registeredAddons['group'] = 
                    array_merge($this->registeredAddons['group'], $_addons);
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
        HandlingInterface $O_RouteHandling, string $route_id): HandlingInterface
    {
        $this->setRouteHandlers->invokeArgs(
            $O_RouteHandling,
            [
                & $this->routesAddonsDataCurrentIndex[$route_id],
                $route_id
            ]
        );

        $i = 0;
        if (!empty($this->RouteHandlingDecorator))
        {
            foreach ($this->RouteHandlingDecorator as $decorator) {
                if ($i === 0) {
                    $RouteHandling = new $decorator($O_RouteHandling);
                    $i++;
                }

                $RouteHandling = new $decorator($RouteHandling);
            }
        } else {
            $RouteHandling = $O_RouteHandling;
        } 
       
       $O_RouteHandling->setChild($RouteHandling);
        
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

        $O_GroupHandling = $this->getGroupHandling();

        $previousIndex = & $this->groupsAddonsDataPreviousIndex();

        $this->setGroupHandlers->invokeArgs(
            $O_GroupHandling,
            [
                & $this->routesAddonsDataCurrentIndex['*addons*']
            ]
        );

        $i = 0;
        if (!empty($this->GroupHandlingDecorator))
        {
            foreach ($this->GroupHandlingDecorator as $decorator) 
            {
                if ($i === 0) {
                    $GroupHandling = new $decorator($O_GroupHandling);
                    $i++;
                }

                $GroupHandling = new $decorator($GroupHandling);
            }
        } else {
            $GroupHandling = $O_GroupHandling;
        }

        $O_GroupHandling->setChild($GroupHandling);
        
        $previousIndex[spl_object_id($GroupHandling)] = & $previousIdx;

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
    protected function & groupsAddonsDataPreviousIndex(): array 
    {
        return self::$groupsAddonsDataPreviousIndex;
    }
}