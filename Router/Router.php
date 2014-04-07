<?php

namespace Ticketpark\ExpiringUrlBundle\Router;

use Symfony\Bundle\FrameworkBundle\Routing\Router as BaseRouter;
use Ticketpark\ExpiringUrlBundle\Creator\Creator;

/**
 * Router
 *
 * Overwrites the existing router (see Ticketpark\ExpiringUrlBundle\DependencyInjection\Compiler\RouterPass).
 * Takes care of expiration hash parameters in routes so they don't have to be added manually when creating urls.
 *
 * Example:
 *
 * routing.yml:
 *   expiring_route:
 *     pattern: /some/url/{expirationHash}/{id}
 *     defaults: { _controller: AcmeBundle:Controller:someAction }
 *     options:
 *       expiring_url_identifier: 'id'  # optional
 *       expiring_url_ttl: 30           # optional
 *
 * Controller.php:
 *   $url = $this->get('router')->generate('expiring_route', array('id' => $id));
 */
class Router extends BaseRouter
{
    /**
     * The expiration hash creator
     *
     * @var Creator
     */
    protected $creator;

    /**
     * The route parameter name in routes
     *
     * @var string
     */
    protected $routeParameterName;

    /**
     * Set Creator
     *
     * @param Creator $creator
     */
    public function setCreator(Creator $creator)
    {
        $this->creator = $creator;
    }

    /**
     * Set route parameter name
     *
     * @param string $routeParameterName
     */
    public function setRouteParameterName($routeParameterName)
    {
        $this->routeParameterName = $routeParameterName;
    }

    /**
     * {@inheritdoc}
     */
    public function generate($name, $parameters = array(), $referenceType = self::ABSOLUTE_PATH)
    {
        if (!$this->creator instanceof Creator) {
            throw new \InvalidArgumentException('You must set a Creator first with setCreator()');
        }

        if (null == $this->routeParameterName) {
            throw new \InvalidArgumentException('You must set a route parameter first with setRouteParameter()');
        }

        $routeCollection = $this->getRouteCollection();
        $route           = $routeCollection->get($name);
        $routeVariables  = $route->compile()->getVariables();

        if (
            // the route must contain the route parameter name we are looking for
            in_array($this->routeParameterName, $routeVariables)

            // and it must not have been set yet
            && !array_key_exists($this->routeParameterName, $parameters))
        {
            $options = $route->getOptions();

            $identifier = null;
            if (array_key_exists('expiring_url_identifier', $options) && in_array($options['expiring_url_identifier'], $routeVariables)) {
                $identifier = $parameters[$options['expiring_url_identifier']];
            }

            $ttlInMinutes = null;
            if (array_key_exists('expiring_url_ttl', $options)) {
                $ttlInMinutes = $options['expiring_url_ttl'];
            }

            $parameters[$this->routeParameterName] = $this->creator->create($identifier, $ttlInMinutes);
        }

        return parent::generate($name, $parameters, $referenceType);
    }
}