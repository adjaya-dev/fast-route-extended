<?php

declare(strict_types=1);

namespace Adjaya\FastRoute\DataGenerator;

use Adjaya\FastRoute\Exception\BadRouteException;
use Exception;

abstract class RegexBasedAbstract implements DataGeneratorInterface
{
    protected $identicalsRegexRoutes = true;

    protected $groupsStack = [];

    /** @var mixed[][] */
    protected $staticRoutes = [];

    /** @var mixed[][] */
    protected $methodToRegexToRoutesMap = [];

    /**
     * @return int
     */
    abstract protected function getApproxChunkSize(): int;

    /**
     * @return mixed[]
     */
    abstract protected function processChunk(array $regexToRoutesMap): array;

    public function allowIdenticalsRegexRoutes(bool $allow = true): void
    {
        $this->identicalsRegexRoutes = $allow;
    }

    public function addGroup(array $groupData, string $groupId, ?string $parentGroupId = null): void
    {
        list($groupRegex, $groupVariables) = $this->buildRegexForGroup($groupData);

        $mergedRegex = (array) $groupRegex;
        $mergedVariables = $groupVariables;

        if ($parentGroupId) {
            $parentGroup = $this->groupsStack[$parentGroupId];
            $parentsRegex = $parentGroup->regexMergedWithParents;
            $parentsVariables = $parentGroup->variablesMergedWithParents;

            $mergedRegex = array_merge($parentsRegex, (array) $groupRegex);
            $mergedVariables = array_merge($parentsVariables, $groupVariables);
        }

        $this->groupsStack[$groupId] = new Group(
            $groupId,
            $groupRegex,
            $groupVariables,
            $parentGroupId,
            $mergedRegex,
            $mergedVariables
        );
    }

    public function getGroupData(string $groupId): array
    {
        return $this->groupsStack[$groupId]->getMergedData();
    }

    public function addRoute($httpMethod, array $routeData, string $routeId, ?string $groupId = null)
    {
        if ($this->isStatic($routeData)) {
            $this->addStaticRoute($httpMethod, $routeData[0][0], $routeId);
        } else {
            $this->addVariableRoute($httpMethod, $routeData, $routeId, $groupId);
        }
    }

    /**
     * @param mixed[]
     *
     * @return bool
     */
    protected function isStatic(array $routeData): bool
    {
        return 1 === \count($routeData) && 1 === \count($routeData[0]) && \is_string($routeData[0][0]);
    }

    private function addStaticRoute($httpMethod, string $routeStr, string $routeId): void
    {
        foreach ((array) $httpMethod as $method) {
            if (// Allow Multiple routes matching same regex ?
                !$this->identicalsRegexRoutes
                &&
                isset($this->staticRoutes[$method][$routeStr])
            ) {
                throw new Exception('
                    Identicals regular expressions for multiple routes are not allowed.
                    You can set this option to true if you want, 
                    see function allowIdenticalsRegexRoutes(bool $allow = true).
                ');
            }

            $this->staticRoutes[$method][$routeStr][] = $routeId;
        }
    }

    protected function addVariableRoute($httpMethod, array $routeData, string $routeId, ?string $groupId)
    {
        list($route_regex, $variables) = $this->buildRegexForRoute($routeData);

        $route_regex_string = $route_regex;

        $prefix_regex_array = [];

        if ($groupId) {
            $prefix_regex_array = $this->groupsStack[$groupId]->regexMergedWithParents;
            $group_regex_string = implode('', $prefix_regex_array);
            // We search the regex of the road without the regex of the group.
            $route_regex_string = str_replace($group_regex_string, '', $route_regex_string);
        }

        foreach ((array) $httpMethod as $method) {
            if (// Allow Multiple routes matching same regex ?
                !$this->identicalsRegexRoutes
                &&
                isset($this->methodToRegexToRoutesMap[$method][$route_regex])
            ) {
                throw new Exception('
                    Identicals regular expressions for multiple routes are not allowed.
                    You can set this option to true if you want, 
                    see function allowIdenticalsRegexRoutes(bool $allow = true).
                ');
            }

            $this->methodToRegexToRoutesMap[$method][$route_regex][] = new Route(
                $method,
                $routeId,
                $route_regex_string,
                $variables,
                $groupId,
                $prefix_regex_array
            );
        }
    }

    protected function buildRegexForGroup(array $groupData): array
    {
        if (1 !== \count($groupData)) {
            throw new BadRouteException(
                'Cannot use optional placeholder in a group prefix'
            );
        }

        list($regex, $variables) = $this->buildRegex($groupData);

        return [implode('', $regex), $variables];
    }

    /**
     * @param mixed[]
     *
     * @return mixed[]
     */
    private function buildRegexForRoute(array $routeData): array
    {
        $reg_f = '%s';
        $reg_optional_start_f = '(?:%s';
        $reg_optional_end_f = ')?';

        $count_optional = \count($routeData) - 1;
        if ($count_optional >= 1) { // optional segments
            $reg_f .=
                str_repeat($reg_optional_start_f, $count_optional) .
                str_repeat($reg_optional_end_f, $count_optional);
        }

        list($regex, $variables) = $this->buildRegex($routeData);

        $regex = vsprintf($reg_f, $regex);

        return [$regex, $variables];
    }

    protected function buildRegex(array $data): array
    {
        $regex = [];
        $variables = [];

        foreach ($data as $i => $segments) {
            $regex[$i] = '';
            foreach ($segments as $segment) {
                if (\is_string($segment)) {
                    $regex[$i] .= preg_quote($segment);
                    continue;
                }
                list($varName, $regexPart) = $segment;

                if (isset($variables[$varName])) {
                    throw new BadRouteException(sprintf(
                            'Cannot use the same placeholder "%s" twice',
                            $varName
                        ));
                }

                if ($this->regexHasCapturingGroups($regexPart)) {
                    throw new BadRouteException(sprintf(
                            'Regex "%s" for parameter "%s" contains a capturing group',
                            $regexPart,
                            $varName
                        ));
                }

                $variables[$varName] = $varName;
                $regex[$i] .= '(' . $regexPart . ')';
            }
        }

        return [$regex, $variables];
    }

    /**
     * @return mixed[]
     */
    public function getData(): array
    {
        if (empty($this->methodToRegexToRoutesMap)) {
            return [$this->staticRoutes, []];
        }

        return [$this->staticRoutes, $this->generateVariableRouteData()];
    }

    /**
     * @return mixed[]
     */
    private function generateVariableRouteData()
    {
        $data = [];

        foreach ($this->methodToRegexToRoutesMap as $method => $regexToRoutesMap) {
            $chunkSize = $this->computeChunkSize(\count($regexToRoutesMap));
            $chunks = array_chunk($regexToRoutesMap, $chunkSize, true);
            $data[$method] = array_map([$this, 'processChunk'], $chunks); // nouvelle methode
            //$data[$method] = array_map([$this, '_processChunk'], $chunks); // ancienne methode
        }

        return $data;
    }

    /**
     * @param int
     *
     * @return int
     */
    private function computeChunkSize($count)
    {
        $numParts = max(1, round($count / $this->getApproxChunkSize()));

        return (int) ceil($count / $numParts);
    }

    /**
     * @param string
     *
     * @return bool
     */
    private function regexHasCapturingGroups($regex)
    {
        if (false === mb_strpos($regex, '(')) {
            // Needs to have at least a ( to contain a capturing group
            return false;
        }

        // Semi-accurate detection for capturing groups
        return (bool) preg_match(
            '~
                (?:
                    \(\?\(
                  | \[ [^\]\\\\]* (?: \\\\ . [^\]\\\\]* )* \]
                  | \\\\ .
                ) (*SKIP)(*FAIL) |
                \(
                (?!
                    \? (?! <(?![!=]) | P< | \' )
                  | \*
                )
            ~x',
            $regex
        );
    }
}
