<?php

namespace Ticketpark\ExpiringUrlBundle\Router;

use JMS\I18nRoutingBundle\Router\I18nRouter;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouterInterface;
use Ticketpark\ExpiringUrlBundle\Creator\Creator;
use Ticketpark\FileBundle\FileHandler\FileHandler;

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
class Router implements RouterInterface
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
     * FileHandler, to handle caching
     *
     * @var FileHandler
     */
    protected $fileHandler;

    /**
     * RouteCollection, from cache for performance reasons
     *
     * @var RouteCollection
     */
    protected $routeCollection;

    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * @var string
     */
    protected $defaultLocale;

    /**
     * Constructor
     *
     * @param string|null $defaultLocale
     */
    public function __construct($defaultLocale = null)
    {
        $this->defaultLocale = $defaultLocale;
    }

    /**
     * Set parent router
     *
     * @param RouterInterface $router
     */
    public function setParentRouter(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * Get parent router
     *
     * @return RouterInterface
     */
    public function getParentRouter()
    {
        return $this->router;
    }

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
     * Set file handler
     *
     * @param FileHandler $fileHandler
     */
    public function setFileHandler(FileHandler $fileHandler)
    {
        $this->fileHandler = $fileHandler;
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

        // getRouteCollection() is sloooooow!
        // So we cache the response.
        // @fixme: How could this be solved better?
        //
        // https://github.com/symfony/symfony/issues/4436
        // https://github.com/symfony/symfony/issues/11171

        if (null == $this->routeCollection) {
            $identifier = 'ticketpark_expiring_bundle_route_collection';
            if ($file = $this->fileHandler->fromCache($identifier)) {
                $this->routeCollection = unserialize(file_get_contents($file));
            } else {
                $this->routeCollection = $this->getRouteCollection();
                $this->fileHandler->cache(serialize($this->routeCollection), $identifier);
            }
        }

        // Explicit support for combination with JMSI18nRoutingBundle
        $route = $this->routeCollection->get($name);
        if ($this->getParentRouter() instanceof I18nRouter) {
            $currentLocale = $this->getContext()->getParameter('_locale');
            if (isset($parameters['_locale'])) {
                $locale = $parameters['_locale'];
            } elseif ($currentLocale) {
                $locale = $currentLocale;
            } else {
                $locale = $this->defaultLocale;
            }

            if (!$route = $this->routeCollection->get($name)) {
                $route = $this->routeCollection->get(($locale.'__RG__'.$name));
            }
        }

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

        return $this->getParentRouter()->generate($name, $parameters, $referenceType);
    }

    public function match($pathinfo)
    {
        return $this->getParentRouter()->match($pathinfo);
    }

    public function getRouteCollection()
    {
        return $this->getParentRouter()->getRouteCollection();
    }

    public function setContext(RequestContext $context)
    {
        $this->getParentRouter()->setContext($context);
    }

    public function getContext()
    {
        return $this->getParentRouter()->getContext();
    }
}