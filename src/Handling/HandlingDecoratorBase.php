<?php

declare(strict_types=1);

namespace Adjaya\FastRoute\Handling;

use BadMethodCallException;

class HandlingDecoratorBase implements HandlingDecoratorInterface
{
    /**
     * @var HandlingInterface
     */
    protected $Handling;

    public function __construct(HandlingInterface $Handling)
    {
        $this->Handling = $Handling;
    }

    public function getChild(): HandlingInterface 
    {
        return $this->Handling->getChild();
    }

    public function __call($method, $parameters): HandlingInterface
    {
        call_user_func_array(array($this->Handling, $method), $parameters);

        return $this->getChild();
    }

    public static function __callStatic($method, $parameters): BadMethodCallException
    {
        throw new BadMethodCallException("Method __callStatic is not allowed, can't call {$method}");
    }

    public function add(array $_addons): HandlingInterface
    {
        $this->Handling->add($_addons);

        return $this->getChild();
    }
}