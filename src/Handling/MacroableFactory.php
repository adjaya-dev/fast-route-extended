<?php

declare(strict_types=1);

namespace Adjaya\FastRoute\Handling;

class MacroableFactory 
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

    public function create(): array 
    {
        return [$this->macroableClass => $this->macros];
    }
}
