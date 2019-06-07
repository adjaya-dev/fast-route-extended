<?php

declare(strict_types=1);

namespace Adjaya\FastRoute\Handling;

use ReflectionMethod;
use Exception;

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
     * @var ReflectionMethod RouteHandling::setHandlers
     */
    protected $setRouteHandlers;

    /**
     * @var ReflectionMethod GroupHandling::setHandlers
     */
    protected $setGroupHandlers;

    protected static $addonsScopes = ['global', 'route', 'group'];

    protected $RouteHandlingDecorator = [];

    protected $GroupHandlingDecorator = [];

    public function __construct(?array $options = [])
    {
        $this->routesAddonsDataCurrentIndex = & $this->routesAddonsData;

        $this->setOptions($options);
    }

    public function getRegisteredAddons(): array 
    {
        return $this->registeredAddons;
    }

    public function setRouteHandlingDecorator(string $routeHandlingDecoratorClass): void
    {
        $this->RouteHandlingDecorator[] = $routeHandlingDecoratorClass;
    }

    public function setGroupHandlingDecorator(string $groupHandlingDecoratorClass): void
    {
        $this->GroupHandlingDecorator[] = $groupHandlingDecoratorClass;
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

    protected function registerAddons(array $addons_stack): array 
    {
        foreach ($addons_stack as $scope => $addons) 
        {
            if (!in_array($scope, self::$addonsScopes)) {
                $mes = implode(' or ', self::$addonsScopes);
                throw new Exception("Scope $scope not exists, must be $mes"); 
            }

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

        return $_addons;
    }

    public function processAddons(array & $routesData): void
    {
        /*
        var_dump('process addons');
        echo '<pre>';
        print_r($this->routesAddonsData);
        echo '</pre>';
        */
        
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
        if (!$this->RouteHandling) 
        {
            $this->setRouteHandlers =
                new ReflectionMethod(RouteHandling::class, 'setHandlers');

            $this->setRouteHandlers->setAccessible(true);

            $this->RouteHandling = $O_RouteHandling =
                new RouteHandling($this->registeredAddons['route']);

            if (!empty($this->RouteHandlingDecorator))
            {
                foreach ($this->RouteHandlingDecorator as $decorator) 
                {
                    $this->RouteHandling = new $decorator($this->RouteHandling);
                }
            }
            
            $O_RouteHandling->setChild($this->RouteHandling);
        }

        return $this->RouteHandling;
    }

    /**
     * @return HandlingInterface new $this->GroupHandling instance
     */
    protected function getGroupHandling(): HandlingInterface
    {
        if (!$this->GroupHandling) 
        {
            $this->setGroupHandlers =
                new ReflectionMethod(GroupHandling::class, 'setHandlers');

            $this->setGroupHandlers->setAccessible(true);
        }
    
        $this->GroupHandling = $O_GroupHandling =
        new GroupHandling($this->registeredAddons['group']);

        if (!empty($this->GroupHandlingDecorator))
        {
            foreach ($this->GroupHandlingDecorator as $decorator) 
            {
                $this->GroupHandling = new $decorator($this->GroupHandling);
            }
        }

        $O_GroupHandling->setChild($this->GroupHandling);

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
        $O_RouteHandling = $RouteHandling;

        if ($RouteHandling instanceof HandlingDecoratorInterface)
        {
            $O_RouteHandling = $RouteHandling->getOriginalHandling();
        }

        $this->setRouteHandlers->invokeArgs(
            $O_RouteHandling,
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

        $previousIndex = & $this->groupsAddonsDataPreviousIndex();

        $O_GroupHandling = $GroupHandling = $this->getGroupHandling();

        if ($GroupHandling instanceof HandlingDecoratorInterface)
        {
            $O_GroupHandling = $GroupHandling->getOriginalHandling();
        }

        $this->setGroupHandlers->invokeArgs(
            $O_GroupHandling,
            [
                & $this->routesAddonsDataCurrentIndex['*addons*']
            ]
        );

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