<?php

declare(strict_types=1);

namespace Adjaya\FastRoute;

class Handling extends AbstractHandling implements HandlingInterface
{
    use \Spatie\Macroable\Macroable;

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

    public function __construct(?array $addons = [])
    {
        $this->registerAddons($addons);
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
    public function add(array $_addons): HandlingInterface
    {
        $this->addons = $this->pushAddons($_addons, (array) $this->addons);
        
        return $this;
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
