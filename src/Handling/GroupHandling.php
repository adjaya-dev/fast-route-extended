<?php

declare(strict_types=1);

namespace Adjaya\FastRoute\Handling;

class GroupHandling extends Handling
{
    public function __construct(?array $addons = [])
    {
        $addons['*group*'] = ['prefix', 'name', 'hosts', 'schemes'];
        parent::__construct($addons);
    }

    public function prefix(string $prefix)
    {
        if (isset($this->getAddons()['*group*']['prefix'])) {
            throw new \Exception("Error Processing Request", 1);
        }

        return $this->add(['*group*' => ['prefix' => $prefix]]);
    }
}
