<?php

declare(strict_types=1);

namespace Adjaya\FastRoute;

class HandlingDecoratorBase implements HandlingDecoratorInterface
{
    protected $Handling;

    public function __construct(HandligInterface $Handling)
    {
        $this->Handling = $Handling;
    }

    public function add(array $_addons): HandlingInterface
    {
        return $this->Handling->add($_addons);
    }
}