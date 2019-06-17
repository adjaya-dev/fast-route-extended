<?php

declare(strict_types=1);

namespace Adjaya\FastRoute;

class Configurator implements ConfiguratorInterface
{
    protected $options;

    protected $class;

    public function getOptions(): array
    {
        return $this->options;
    }

    public function getClass(): string
    {
        return $this->class;
    }

    public function provide(): array
    {
        return [$this->getClass, $this->getOptions];
    }
}
