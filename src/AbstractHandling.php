<?php

declare(strict_types=1);

namespace Adjaya\FastRoute;

abstract class AbstractHandling
{
    abstract protected function setHandlers(?array & $addons, ?string $id = null): void;
}