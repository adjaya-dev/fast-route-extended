<?php

declare(strict_types=1);

namespace Adjaya\FastRoute\Handling;

abstract class AbstractHandling
{
    abstract protected function setHandlers(?array &$addons, ?string $id = null): void;

    abstract public function getId(): ?string;

    abstract public function getRegisteredAddons(): array;

    abstract public function getAddons(): ?array;

    abstract public function setChild(HandlingDecoratorInterface $child): void;

    abstract public function getChild(): HandlingInterface;
}
