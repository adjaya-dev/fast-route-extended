<?php

declare(strict_types=1);

namespace Adjaya\FastRoute\Addon;

use Adjaya\FastRoute\ConfigurationInterface;
use Adjaya\FastRoute\Handling\HandlingProviderDecoratorConfigurationInterface;

interface AddonConfigurationInterface extends ConfigurationInterface
{
    public function setHandlingProvider(string $handlingProviderClass): AddonConfigurationInterface;
    
    public function addHandlingProviderDecorator(
        HandlingProviderDecoratorConfigurationInterface $decorator): AddonConfigurationInterface;
}
