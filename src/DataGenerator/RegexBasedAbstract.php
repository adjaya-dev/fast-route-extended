<?php

declare(strict_types=1);

namespace Adjaya\FastRoute\DataGenerator;

use Adjaya\FastRoute\Group;
use FastRoute\BadRouteException;
use FastRoute\DataGenerator;
use Adjaya\FastRoute\Route;

abstract class RegexBasedAbstract implements DataGenerator
{
    protected $groupsStack = [];

    /** @var mixed[][] */
    protected $staticRoutes = [];
    
    /** @var mixed[][] */
    protected $methodToRegexToRoutesMap = [];
    
    /**
     * @return int
     */
    abstract protected function getApproxChunkSize();

    /**
     * @return mixed[]
     */
    abstract protected function processChunk($regexToRoutesMap);

    public function addGroup($groupData, $groupId, $parentGroupId) 
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
            $groupId, $groupRegex, $groupVariables, $parentGroupId, $mergedRegex, $mergedVariables
        );
    }
    // pas utilisé
    public function getGroupData($groupId) {
        return $this->groupsStack[$groupId]->getMergedData();
    }

    public function addRoute($httpMethod, $routeData, $routeId, $groupId = null) 
    {
        if ($this->isStatic($routeData)) {
            $this->addStaticRoute($httpMethod, $routeData, $routeId);
        } else {
            $this->addVariableRoute($httpMethod, $routeData, $routeId, $groupId);
        }
    }

    protected function addVariableRoute($httpMethod, $routeData, $routeId, $groupId)
    {
        list($route_regex, $variables) = $this->buildRegexForRoute($routeData);

        $route_regex_string = $route_regex;

        $group_regex_array = [];

        if ($groupId) {
            $group_regex_array = $this->groupsStack[$groupId]->regexMergedWithParents;
            $group_regex_string = implode($group_regex_array);
            // On cherche la regex de la route sans la regex du group :
            $route_regex_string = str_replace($group_regex_string, '', $route_regex_string);
        }

        // Allow Multiple routes matching same regex
        foreach((array) $httpMethod as $method) 
        {
            $this->methodToRegexToRoutesMap[$method][$route_regex][] = new Route( 
                $method, $routeId, $route_regex_string, $variables, $groupId, $group_regex_array
            );
        }
    }

    /**
     * @param mixed[]
     * @return bool
     */
    protected function isStatic($routeData) 
    {
        return count($routeData) === 1 && count($routeData[0]) === 1 && is_string($routeData[0][0]);    
    }

    private function addStaticRoute($httpMethod, $routeData, $routeId)
    {
        $routeStr = $routeData[0][0];
        foreach((array) $httpMethod as $method) {
            // TODO test à faire quand toutes les variables routes sont definies !!!!!!!!!!!!!!!!!!!
            /*
            if (isset($this->_methodToRegexToRoutesMap[$method])) {
                foreach ($this->_methodToRegexToRoutesMap[$method] as $routes) {
                    // Allow Multiple routes matching same regex
                    foreach ($routes as $key => $route) {
                        if ($route->matches($routeStr)) {
                            throw new BadRouteException(sprintf(
                                'Static route "%s" is shadowed by previously defined variable route "%s" for method "%s"',
                                $routeStr, $route->regex, $httpMethod
                            ));
                        }
                    }
                }
            }
            */
            // Allow Multiple routes matching same regex
            $this->staticRoutes[$method][$routeStr][] = $routeId;
        } 
    }

    protected function buildRegexForGroup($groupData)
    {
        if (count($groupData) !== 1) {
            throw new BadRouteException(
                'Cannot use optional placeholder in a group prefix');       
        }

        return $this->buildRegex($groupData);
    }

    /**
     * @param mixed[]
     * @return mixed[]
     */
    private function buildRegexForRoute($routeData)
    {
        $reg_f = '%s';
        $reg_optional_open_f = '(?:%s';
        $reg_optional_close_f = ')?';

        $count_optional = count($routeData) -1;
        if ($count_optional >= 1) // optional segments
        {
            $reg_f .= 
                str_repeat($reg_optional_open_f, $count_optional).
                str_repeat($reg_optional_close_f, $count_optional);
        }

        list($regex, $variables) = $this->buildRegex($routeData);

        $regex = vsprintf($reg_f, $regex);

        return [$regex, $variables];
    }

    protected function buildRegex($data) 
    {
        $regex = [];
        $variables = [];
   
        foreach ($data as $i => $segments) 
        {
            $regex[$i] = '';
            foreach ($segments as $segment) {
                
                if (is_string($segment)) {
                    $regex[$i] .= preg_quote($segment);
                    continue;
                } else {
                    list($varName, $regexPart) = $segment;

                    if (isset($variables[$varName])) {
                        throw new BadRouteException(sprintf(
                            'Cannot use the same placeholder "%s" twice', $varName
                        ));
                    }

                    if ($this->regexHasCapturingGroups($regexPart)) {
                        throw new BadRouteException(sprintf(
                            'Regex "%s" for parameter "%s" contains a capturing group',
                            $regexPart, $varName
                        ));
                    }

                    $variables[$varName] = $varName;
                    $regex[$i] .= '(' . $regexPart . ')';
                }
            }
        }

        return [$regex, $variables];
    }

    /**
     * @return mixed[]
     */
    public function getData()
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
            $chunkSize = $this->computeChunkSize(count($regexToRoutesMap));
            $chunks = array_chunk($regexToRoutesMap, $chunkSize, true);
            $data[$method] = array_map([$this, 'processChunk'], $chunks); // nouvelle methode
            //$data[$method] = array_map([$this, '_processChunk'], $chunks); // ancienne methode
        }
        return $data;
    }

    /**
     * @param int
     * @return int
     */
    private function computeChunkSize($count)
    {
        $numParts = max(1, round($count / $this->getApproxChunkSize()));
        return (int) ceil($count / $numParts);
    }

    /**
     * @param string
     * @return bool
     */
    private function regexHasCapturingGroups($regex)
    {
        if (false === strpos($regex, '(')) {
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
