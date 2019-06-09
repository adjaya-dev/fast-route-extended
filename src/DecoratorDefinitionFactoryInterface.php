<?php

declare(strict_types=1);

namespace Adjaya\FastRoute;

interface DecoratorDefinitionFactoryInterface
{
    public function create(): array;
}
