<?php

declare(strict_types=1);

namespace Adjaya\FastRoute\Addon;

use Adjaya\FastRoute\ConfiguratorInterface;
use Adjaya\FastRoute\Handling\HandlingProviderDecoratorConfiguratorInterface;

interface AddonConfiguratorInterface extends ConfiguratorInterface
{
    public function setHandlingProvider(string $handlingProviderClass): AddonConfiguratorInterface;
    
    public function addHandlingProviderDecorator(
        HandlingProviderDecoratorConfiguratorInterface $decorator): AddonConfiguratorInterface;
}
