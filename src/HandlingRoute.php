<?php

declare(strict_types=1);

namespace Adjaya\FastRoute;

class HandlingRoute implements HandlingRouteInterface
{
    protected $route;
    
    public function __construct(Route $route)
    {
        $this->route = $route;
    }

    public function name(string $name): self
    {
        $this->route->setName($name);

        return $this;
    }

    public function path(string $path): self
    {
        $this->route->setPath($path);

        return $this;
    }

    public function methods($httpMethods): self
    {
        $this->route->setHttpMethods($httpMethods);

        return $this;
    }

    public function controller($controller): self
    {
        $this->route->setHandler($controler);

        return $this;
    }
}