<?php

namespace Ticketpark\ExpiringUrlBundle\Tests\Checker;


use Symfony\Component\HttpKernel\HttpKernelInterface;
use Ticketpark\ExpiringUrlBundle\Checker\Checker;
use Ticketpark\ExpiringUrlBundle\Creator\Creator;

class CheckerTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->requestType = HttpKernelInterface::MASTER_REQUEST;
        $this->routeOptions = array();
        $this->routeVariables = array(
            'foo',
            'bar',
            'expirationHash'
        );

        $this->checker = new Checker('secret', $this->getRouterMock(), 'expirationHash');
    }

    /**
     * @dataProvider validExpirationHashProvider
     */
    public function testValidExpirationHash($hash)
    {
        $this->hash = $hash;
        $this->assertNull($this->checker->onKernelRequest($this->getEventMock()));
    }

    public function validExpirationHashProvider()
    {
        $creator = new Creator(30, 'secret');

        return array(
            array($creator->create()),
            array($creator->create(null, 1)),
            array($creator->create(null, null)),
        );
    }

    /**
     * @dataProvider expiringUrlIdentifierProvider
     */
    public function testExpiringUrlIdentifier($hash)
    {
        $this->routeOptions = array(
            'expiring_url_identifier' => 'foo'
        );
        $this->hash = $hash;
        $this->assertNull($this->checker->onKernelRequest($this->getEventMock()));
    }

    public function expiringUrlIdentifierProvider()
    {
        $creator = new Creator(30, 'secret');

        return array(
            array($creator->create(123)),
            array($creator->create(123, 1)),
            array($creator->create(123, null)),
        );
    }

    /**
     * @expectedException \Ticketpark\ExpiringUrlBundle\Exception\UrlExpiredException
     * @dataProvider expiredOrInvalidExpirationHashProvider
     */
    public function testExpiredOrInvalidExpirationHash($hash)
    {
        $this->hash = $hash;
        $this->checker->onKernelRequest($this->getEventMock());
    }

    public function expiredOrInvalidExpirationHashProvider()
    {
        $creator = new Creator(30, 'secret');

        return array(
            array('foo'),
            array(null),
            array(''),
            array(substr($creator->create(), 0, 1)), //tampered
            array($creator->create(-1)), //expired
            array('2014-04-07T13:28:32+02:00.256dd8ddacfa5a98532dc6122f0e77834a42cba6d49cb43d4e0ca31e0f55ce64') // expired
        );
    }

    public function testNonMasterRequest()
    {
        $this->requestType = 'foo';
        $this->assertNull($this->checker->onKernelRequest($this->getEventMock()));
    }

    public function getEventMock()
    {
        $event = $this->getMockBuilder('Symfony\Component\HttpKernel\Event\GetResponseEvent')
            ->disableOriginalConstructor()
            ->setMethods(array('getRequest', 'getRequestType'))
            ->getMock();

        $event->expects($this->any())
            ->method('getRequest')
            ->will($this->returnCallback(array($this, 'getRequestMock')));

        $event->expects($this->any())
            ->method('getRequestType')
            ->will($this->returnValue($this->requestType));

        return $event;
    }

    public function getRequestMock()
    {
        $request = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')
            ->disableOriginalConstructor()
            ->setMethods(array('get'))
            ->getMock();

        $request->expects($this->any())
            ->method('get')
            ->will($this->returnCallback(array($this, 'getRequestParameter')));

        return $request;
    }

    public function getRouterMock()
    {
        $router = $this->getMockBuilder('Symfony\Component\Routing\Router')
            ->disableOriginalConstructor()
            ->setMethods(array('getRouteCollection'))
            ->getMock();

        $router->expects($this->any())
            ->method('getRouteCollection')
            ->will($this->returnCallback(array($this, 'getRouteCollectionMock')));

        return $router;
    }

    public function getRouteCollectionMock()
    {
        $routeCollection = $this->getMockBuilder('Symfony\Component\Routing\RouteCollection')
            ->disableOriginalConstructor()
            ->setMethods(array('get'))
            ->getMock();

        $routeCollection->expects($this->any())
            ->method('get')
            ->will($this->returnCallback(array($this, 'getRouteMock')));

        return $routeCollection;
    }

    public function getRouteMock()
    {
        $route = $this->getMockBuilder('Symfony\Component\Routing\Route')
            ->disableOriginalConstructor()
            ->setMethods(array('compile', 'getOptions'))
            ->getMock();

        $route->expects($this->any())
            ->method('compile')
            ->will($this->returnCallback(array($this, 'getCompiledRouteMock')));

        $route->expects($this->any())
            ->method('getOptions')
            ->will($this->returnValue($this->routeOptions));

        return $route;
    }

    public function getCompiledRouteMock()
    {
        $compiledRoute = $this->getMockBuilder('Symfony\Component\Routing\CompiledRoute')
            ->disableOriginalConstructor()
            ->setMethods(array('getVariables'))
            ->getMock();

        $compiledRoute->expects($this->any())
            ->method('getVariables')
            ->will($this->returnValue($this->routeVariables));

        return $compiledRoute;
    }

    public function getRequestParameter()
    {
        switch(func_get_arg(0)) {
            case 'expirationHash':
                return $this->hash;

            case 'foo':
                return 123;
        }
    }
}