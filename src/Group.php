<?php

declare(strict_types=1);

namespace Adjaya\FastRoute;

class Group
{
    /**
     * @var string
     */
    public $id;

    /**
     * @var string
     */
    public $regex;

    /**
     * @var array
     */
    public $variables;

    /**
     * @var string
     */
    public $parentId;

    /**
     * @var array
     */
    public $regexMergedWithParents = [];

    /**
     * @var array
     */
    public $variablesMergedWithParents = [];

    /**
     * Constructs a group.
     *
     * @param string      $id
     * @param string      $regex
     * @param array       $variables
     * @param string|null $parentId
     * @param array       $mergedRegex
     * @param array       $mergedVariables
     */
    public function __construct(
        string $id,
        string $regex,
        array $variables,
        ?string $parentId,
        array $mergedRegex,
        array $mergedVariables
    ) {
        $this->id = $id;
        $this->regex = $regex;
        $this->variables = $variables;
        $this->parentId = $parentId;
        $this->regexMergedWithParents = $mergedRegex;
        $this->variablesMergedWithParents = $mergedVariables;
    }

    /**
     * @return array
     */
    public function getMergedData(): array
    {
        return [$this->regexMergedWithParents, $this->variablesMergedWithParents];
    }

    /**
     * Tests whether this group matches the given string.
     *
     * @param string $str
     *
     * @return bool
     */
    public function matches(string $str): bool
    {
        $regex = '~^' . implode('', $this->regexMergedWithParents) . '$~';

        return (bool) preg_match($regex, $str);
    }
}
