<?php

declare(strict_types=1);

namespace Adjaya\FastRoute\Handling;

use Exception;

class MacroableConfigurator implements HandlingProviderDecoratorConfiguratorInterface
{
    protected $scopes = ['auto', 'global', 'route', 'group'];
    
    protected $macroableClass = HandlingProviderDecoratorMacroable::class;

    /**
     *  [
     *      'auto'   => true,
     *      'global' => [],
     *      'route'  => [],
     *      'group'  => [],
     *  ]
     *
     * @var array
     */
    protected $macros = ['auto' => true];

    public function __construct(array $options)
    {
        foreach ($options['macroables'] as $scope => $macros) {
            $this->addMacros($scope, $macros);
        }
    }

    public function addMacros(string $scope, $macros): HandlingProviderDecoratorConfiguratorInterface
    {
        if (in_array($scope, $this->scopes)) {
            if ($scope === 'auto' && !$scope) { // Default true
                $this->macros['auto'] = false;
            } else {
                $this->macros[$scope] = (array) $macros;
            }
        } else {
            throw new Exception("Scope $scope not allowed");
        }

        return $this;
    }

    public function provide(): array
    {
        return [$this->macroableClass => $this->macros];
    }
}
