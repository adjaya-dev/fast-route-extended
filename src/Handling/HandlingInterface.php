<?php

declare(strict_types=1);

namespace Adjaya\FastRoute\Handling;

interface HandlingInterface
{
    public function getChild(): self;

    public function add(array $addons): self;
}
