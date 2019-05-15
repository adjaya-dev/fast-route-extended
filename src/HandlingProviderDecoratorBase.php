<?php

declare(strict_types=1);

namespace Adjaya\FastRoute;

class HandlingProviderDecoratorBase implements HandlingProviderInterface
{
    protected $HandlingProvider;

    public function __construct(HandlingProviderInterface $HandlingProvider)
    {
        $this->HandlingProvider = $HandlingProvider;
    }

    public function registerAddons(array $__addons): void 
    {
        $this->HandlingProvider->registerAddons($__addons);
    }

    public function setRouteHandlingDecorator(string $routeHandlingDecoratorClass): void
    {
        $this->HandlingProvider->setRouteHandlingDecorator($routeHanlingDecoratorClass);
    }

    public function setGroupHandlingDecorator(string $groupHandlingDecoratorClass): void
    {
        $this->HandlingProvider->setGroupHandlingDecorator($groupHandlingDecoratorClass);
    }

    public function processAddons(array & $routesData): void
    {
        $this->HandlingProvider->processAddons($routesData);
    }

    public function beforeAddRoute(): HandlingInterface
    {
        return $this->HandlingProvider->beforeAddRoute();
    }

    public function afterAddRoute(
        HandlingInterface $RouteHandling, string $route_id): HandlingInterface
    {
        return $this->HandlingProvider->afterAddRoute($RouteHandling, $route_id);
    }

    public function beforeAddGroup(): HandlingInterface
    {
        return $this->HandlingProvider->beforeAddGroup();
    }

    public function afterAddGroup(HandlingInterface $GroupHandling): HandlingInterface
    {
        return $this->HandlingProvider->afterAddGroup($GroupHandling);
    }

    public function getRegisteredAddons(): array 
    {
        return $this->HandlingProvider->getRegisteredAddons();
    }
}
