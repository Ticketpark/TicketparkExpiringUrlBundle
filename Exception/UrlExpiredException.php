<?php

namespace Ticketpark\ExpiringUrlBundle\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Thrown when an url has expired
 */
class UrlExpiredException extends HttpException
{
    public function __construct()
    {
        parent::__construct(sprintf(410, 'This url has expired'));
    }
}
