<?php

declare(strict_types=1);

namespace Adjaya\FastRoute\Addon;

use Adjaya\FastRoute\Handling\HandlingProviderDecoratorConfiguratorInterface;
use Adjaya\FastRoute\RouteCollectorDecoratorConfiguratorInterface;

interface AddonConfiguratorInterface extends RouteCollectorDecoratorConfiguratorInterface
{
    public function setHandlingProvider(string $handlingProviderClass): self;

    public function addHandlingProviderDecorator(
        HandlingProviderDecoratorConfiguratorInterface $decorator
    ): self;
}
