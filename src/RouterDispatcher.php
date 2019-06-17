<?php

declare(strict_types=1);

namespace Adjaya\FastRoute;

use Psr\Http\Message\RequestInterface;

class RouterDispatcher implements DispatcherInterface
{
    protected $Dispatcher;
    protected $Request;

    public function __construct(DispatcherInterface $Dispatcher, RequestInterface $Request)
    {
        $this->Dispatcher = $Dispatcher;
        $this->Request = $Request;
    }

    public function dispatch($uri)
    {
    }
}
