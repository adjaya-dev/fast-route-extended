<?php

declare(strict_types=1);

namespace Adjaya\FastRoute\Dispatcher;

use Psr\Http\Message\RequestInterface;

interface DispatcherInterface
{
    const NOT_FOUND = 0;
    const FOUND = 1;
    const METHOD_NOT_ALLOWED = 2;

    /**
     * Dispatches against the provided HTTP method verb and URI.
     *
     * Returns array with one of the following formats:
     *
     *     [self::NOT_FOUND, []]
     *     [self::METHOD_NOT_ALLOWED, ['GET', 'OTHER_ALLOWED_METHODS']]
     *     [self::FOUND, ['Array of route params']]
     *
     * @param string $uri
     * @param string RequestInterface $Request
     *
     * @return array
     */
    public function dispatch($httpMethod, $uri, ?RequestInterface $Request = null): array;
}
