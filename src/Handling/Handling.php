<?php

declare(strict_types=1);

namespace Adjaya\FastRoute\Handling;

class Handling extends AbstractHandling implements HandlingInterface
{
    /**
     * @var array
     */
    protected $registeredAddons;

    /**
     * @var string
     */
    protected $id = null;

    /**
     * @var array|null
     */
    protected $addons;

    protected $ChildHandling;

    public function __construct(?array $addons = [])
    {
        $this->registerAddons($addons);
    }

    public function __call($method, $parameters): HandlingInterface
    {
        if (method_exists($this, $method)) {
            call_user_func_array(array($this, $method), $parameters);

            return $this->getChild();
        }

        throw new \BadMethodCallException("Method {$method} does not exist.");
    }
    
    public static function __callStatic($method, $parameters): \BadMethodCallException
    {
        throw new \BadMethodCallException("Method __callStatic is not allowed, can't call {$method}");
    }

    public function setChild(HandlingInterface $child): void 
    {
        $this->ChildHandling = $child;
    }

    public function getChild(): HandlingInterface 
    {
        return $this->ChildHandling ? $this->ChildHandling : $this;
    }

    /**
     * @param array $addons
     */
    protected function registerAddons(array $addons): void
    {
        $this->registeredAddons = (array) $addons;
    }

    /**
     * @param array $_addons
     * @param array initial $addons_stack
     *
     * @return array udapted $addons_stack
     */
    protected function pushAddons(array $_addons, array $addons_stack = []): array
    {
        foreach ($_addons as $type => $addons) 
        {
            if (array_key_exists($type, $this->registeredAddons)) 
            {
                if ($this->registeredAddons[$type] === $type) {
                    foreach ((array) $addons as $addon => $handlers) 
                    {
                        if (is_string($addon)) {
                            $addons_stack[$type][$addon] = $handlers;
                        } else {
                            $addons_stack[$type][] = $handlers;  
                        }
                    }
                }
                else {
                    foreach ($addons as $addon => $handlers) 
                    {
                        if (in_array($addon, $this->registeredAddons[$type])) 
                        {
                            foreach ((array) $handlers as $handler) 
                            {
                                $addons_stack[$type][$addon][] = $handler;
                            }
                        }
                    }
                }
            }
        }

        return $addons_stack;
    }

    /**
     * @param array $_addons
     *
     * @return object $this this Handling instance
     */
    public function add(array $addons): HandlingInterface
    {
        $this->addons = $this->pushAddons($addons, (array) $this->addons);
        
        return $this->getChild();
    }

    /**
     * @param array|null &$addons
     */
    protected function setHandlers(?array & $addons, ?string $id = null): void
    {
        $this->addons = & $addons;
        $this->id = $id;
    }
}
