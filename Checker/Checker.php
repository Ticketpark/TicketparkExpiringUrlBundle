<?php

namespace Ticketpark\ExpiringUrlBundle\Checker;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Router;
use Ticketpark\ExpiringUrlBundle\Exception\UrlExpiredException;

/**
 * Checker
 *
 * Checks if the current route contains an expiration parameter.
 * If yes, checks if the hash expired.
 *
 * Throws http error code 410 (Gone) if expired.
 */
class Checker
{
    public function __construct($secret, Router $router, $routeParameterName)
    {
        $this->secret = $secret;
        $this->router = $router;
        $this->routeParameterName = $routeParameterName;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        // only listen to master request
        if (HttpKernelInterface::MASTER_REQUEST !== $event->getRequestType()) {
            return;
        }

        $request         = $event->getRequest();
        $routeCollection = $this->router->getRouteCollection();
        $route           = $routeCollection->get($request->get('_route'));
        $routeVariables  = $route->compile()->getVariables();

        if (in_array($this->routeParameterName, $routeVariables)) {

            $secret = $this->secret;
            $options = $route->getOptions();
            if (array_key_exists('expiring_url_identifier', $options) && in_array($options['expiring_url_identifier'], $routeVariables)) {
                $secret .= $request->get($options['expiring_url_identifier']);
            }

            $expiringHash = new \ExpiringHash($secret);
            if ('ok' !== $expiringHash->validate($request->get('expirationHash'))) {

                throw new UrlExpiredException();

                $event->stopPropagation();
            }
        }
    }
}