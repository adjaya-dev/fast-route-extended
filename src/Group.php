<?php

declare(strict_types=1);

namespace Adjaya\FastRoute;

class Group
{
    /** @var string */
    public $regex;

    /** @var array */
    public $variables;

    /** @var string */
    public $id;

    /** @var string */
    public $parentId;

    /** @var array */
    public $regexMergedWithParents = [];

    /** @var array */
    public $variablesMergedWithParents = [];

    /**
     * Constructs a group (value object).
     *
     * @param string  $id
     * @param string $regex
     * @param array  $variables
     */
    public function __construct($id, $regex, $variables, $parentId, $mergedRegex, $mergedVariables)
    {
        $this->id = $id;
        $this->regex = $regex;
        $this->variables = $variables;
        $this->parentId = $parentId;
        $this->regexMergedWithParents = $mergedRegex;
        $this->variablesMergedWithParents = $mergedVariables;
    }

    public function getMergedData() 
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
    public function matches($str)
    {
        $regex = '~^' . $this->regex . '$~';
        return (bool) preg_match($regex, $str);
    }
}