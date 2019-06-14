<?php

declare(strict_types=1);

namespace Adjaya\FastRoute;

use Adjaya\FastRoute\RouteCollectorDecoratorInterface;

interface RouteCollectorDecoratorConfiguratorInterface extends ConfiguratorInterface
{
    public function decorate(object $routeCollector): RouteCollectorDecoratorInterface;
} 