<?php

declare(strict_types=1);

namespace Adjaya\FastRoute;

interface HandlingProviderInterface 
{
    public function registerAddons(array $addons_stack): void; 

    public function getRegisteredAddons(): array; 

    public function setRouteHandlingDecorator(string $routeHandlingDecoratorClass);
    
    public function setGroupHandlingDecorator(string $GroupHandlingDecoratorClass);

    public function processAddons(array & $routesData): void;

    public function beforeAddRoute(): HandlingInterface;

    public function afterAddRoute(HandlingInterface $RouteHandling, string $route_id): HandlingInterface;

    public function beforeAddGroup(): HandlingInterface;

    public function afterAddGroup(HandlingInterface $GroupHandling): HandlingInterface;
}