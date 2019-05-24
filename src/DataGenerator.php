<?php

declare(strict_types=1);

namespace Adjaya\FastRoute;

interface DataGenerator
{
    /**
     * Adds a route to the data generator. The route data uses the
     * same format that is returned by RouterParser::parser().
     *
     * The handler doesn't necessarily need to be a callable, it
     * can be arbitrary data that will be returned when the route
     * matches.
     *
     * @param string $httpMethod
     * @param array $routeData
     * @param mixed $handler
     */
    public function addRoute($httpMethod, array $routeData, string $routeId, ?string $groupId = null);

    public function addGroup(array $groupData, string $groupId, ?string $parentGroupId);

    public function allowIdenticalsRegexRoutes(bool $allow = true): void;

    /**
     * Returns dispatcher data in some unspecified format, which
     * depends on the used method of dispatch.
     */
    public function getData(): array;
}
