<?php

namespace Adjaya\FastRoute;

class Route
{
    /** @var string */
    public $httpMethod;

    /** @var string */
    public $regex;

    /** @var array */
    public $variables;

    /** @var string */
    public $id;

    /** @var string */
    public $groupId;

    /** @var array */
    public $prefixRegex;

    /**
     * Constructs a route (value object).
     *
     * @param string $httpMethod
     * @param mixed  $handler
     * @param string $regex
     * @param array  $variables
     */
    public function __construct($httpMethod, $routeId, $regex, $variables, $groupId, $prefixRegex)
    {
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
    public function matches($str)
    {
        $regex = '~^' . $this->regex . '$~';
        return (bool) preg_match($regex, $str);
    }
}
