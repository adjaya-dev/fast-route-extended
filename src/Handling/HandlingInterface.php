<?php

declare(strict_types=1);

namespace Adjaya\FastRoute\Handling;

interface HandlingInterface 
{
    public function getChild(): HandlingInterface;

    public function add(array $addons): HandlingInterface;
}