<?php

declare(strict_types=1);

namespace Adjaya\FastRoute;

interface HandlingInterface 
{
    public function add(array $_addons): HandlingInterface;
}