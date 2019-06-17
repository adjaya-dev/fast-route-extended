<?php

declare(strict_types=1);

namespace Adjaya\FastRoute;

class Route
{
    /**
     * @var string|array
     */
    public $httpMethod;

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
    public $id;

    /**
     * @var string|null
     */
    public $groupId;

    /**
     *  @var array
     */
    public $prefixRegex;

    /**
     * Constructs a route (value object).
     *
     * @param string|array $httpMethod
     * @param string       $routeId
     * @param string       $regex
     * @param array        $variables
     * @param string|null  $groupId
     * @param array        $prefixRegex
     */
    public function __construct(
        $httpMethod,
        string $routeId,
        string $regex,
        array $variables,
        ?string $groupId,
        array $prefixRegex
    ) {
        $this->httpMethod = $httpMethod;
        $this->id = $routeId;
        $this->regex = $regex;
        $this->variables = $variables;
        $this->groupId = $groupId;
        $this->prefixRegex = $prefixRegex;
    }

    /**
     * Tests whether this route matches the given string.
     *
     * @param string $str
     *
     * @return bool
     */
    public function matches(string $str): bool
    {
        $regex = '~^' . implode('', $this->prefixRegex) . $this->regex . '$~';

        return (bool) preg_match($regex, $str);
    }
}
