<?php

declare(strict_types=1);

namespace Adjaya\FastRoute\Handling;

use BadMethodCallException;

class GroupHandlingDecoratorMacro extends HandlingDecoratorBase
{
    use \Spatie\Macroable\Macroable {
        __call as call;
        //__callStatic as callStatic;
    }

    public function __call($method, $parameters)
    {
        if (static::hasMacro($method)) {
            $this->call($method, $parameters);

            return $this->getChild();
        }

        return parent::__call($method, $parameters);
    }

    public static function __callStatic($method, $parameters): BadMethodCallException
    {
        parent::__callStatic($method, $parameters);
    }
}
