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

    public function getOriginalHandling(): HandlingInterface
    {
        $handling = $this->Handling;

        while ($handling instanceof HandlingDecoratorInterface) {
            $handling = $handling->getOriginalHandling();
        }

        return $handling;
    }

    public function __call(string $method, array $parameters)
    {
        return \call_user_func_array([$this->Handling, $method], $parameters);
    }

    public static function __callStatic($method, $parameters): void
    {
        throw new BadMethodCallException("Method __callStatic is not allowed, can't call {$method}");
    }

    public function add(array $addons): HandlingInterface
    {
        return $this->Handling->add($addons);
    }
}
