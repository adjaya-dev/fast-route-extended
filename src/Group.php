<?php

declare(strict_types=1);

namespace Adjaya\FastRoute;

class Group
{
    protected static $idCount = 0;
    protected $id;
    protected $prefix;
    protected $name;
    protected $collection = [];

    public function __construct(?string $prefix = '', ?string $name = '')
    {
        $this->id = 'group_' . self::$idCount++;
        $this->prefix = $prefix;
        $this->name = $name;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function addRoute(Route $route)
    {
        $this->collection[] = $route;
    }

    public function addGroup(Group $group)
    {
        $this->collection[] = $group;
    }

    public function setPrefix(string $prefix)
    {
        $this->prefix = $prefix;
    }

    public function getPrefix(): string 
    {
        return $this->prefix;
    }

    public function setName(string $name)
    {
        $this->name = $name;
    }

    public function getName(): string 
    {
        return $this->name;
    }

    public function getCollection(): array
    {
        return $this->collection;
    }
}
