<?php

declare(strict_types=1);

namespace Adjaya\FastRoute;

class GroupHandling
{
    protected $group;

    public function __construct(Group $group)
    {
        $this->group = $group;
    }

    public function prefix(string $prefix): self
    {
        $this->group->setPrefix($prefix);

        return $this;
    }

    public function name(string $name): self
    {
        $this->group->setName($name);

        return $this;
    }
}