<?php

declare(strict_types=1);

namespace Adjaya\FastRoute\Handling;

class MacroableConfiguration implements HandlingProviderDecoratorConfigurationInterface
{
    protected $macroableClass = HandlingProviderDecoratorMacroable::class;

    /**
     *  [
     *      'global' => [],
     *      'route'  => [],
     *      'group'  => [],
     *  ]
     *
     * @var array
     */
    protected $macros;

    public function __construct(array $macros) 
    {
        $this->macros = $macros;
    }

    public function provide(): array 
    {
        return [$this->macroableClass => $this->macros];
    }
}
