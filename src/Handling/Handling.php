<?php

declare(strict_types=1);

namespace Adjaya\FastRoute\Handling;

use Exception;

class Handling extends AbstractHandling implements HandlingInterface
{
    /**
     * @var array
     */
    private $registeredAddons;

    /**
     * @var string
     */
    private $id = null;

    /**
     * @var array|null
     */
    private $addons;

    private $ChildHandling;

    public function __construct(?array $addons = [])
    {
        $this->registerAddons($addons);
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getRegisteredAddons(): array
    {
        return $this->registeredAddons;
    }

    public function getAddons(): ?array
    {
        return $this->addons;
    }

    public function setChild(HandlingDecoratorInterface $child): void
    {
        if ($this->ChildHandling) {
            throw new Exception(
                "Child Handling is already set."
            );
        }

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
        foreach ($_addons as $type => $addons) {
            if (array_key_exists($type, $this->registeredAddons)) {
                if ($this->registeredAddons[$type] === $type) {
                    foreach ((array) $addons as $addon => $handlers) {
                        if (is_string($addon)) {
                            $addons_stack[$type][$addon] = $handlers;
                        } else {
                            $addons_stack[$type][] = $handlers;
                        }
                    }
                } else {
                    foreach ($addons as $addon => $handlers) {
                        if (in_array($addon, $this->registeredAddons[$type])) {
                            foreach ((array) $handlers as $handler) {
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
