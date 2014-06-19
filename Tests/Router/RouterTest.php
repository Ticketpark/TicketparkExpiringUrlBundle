<?php

namespace Ticketpark\ExpiringUrlBundle\Tests\Router;

use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Config\FileLocator;
use Ticketpark\ExpiringUrlBundle\Creator\Creator;
use Ticketpark\ExpiringUrlBundle\Router\Router;

class RouterTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->router = new Router($this->getContainer(), 'routing.yml');
        $this->router->setCreator($this->getCreator());
        $this->router->setRouteParameterName('expirationHash');
        $this->router->setFileHandler($this->getFileHandlerMock());
    }

    /**
     * @dataProvider routerCreatesExpirationHashAutomaticallyProvider
     */
    public function testRouterCreatesExpirationHashAutomatically($routeName, $expectsHashInUrl)
    {
        $url = $this->router->generate($routeName, array('dummy' => 'dummyParam'));
        $this->assertSame($expectsHashInUrl, $this->containsHash($url));
    }

    public function routerCreatesExpirationHashAutomaticallyProvider()
    {
        return array(
            array('withHash_1', true),
            array('withHash_2', true),
            array('noHash_1', false),
            array('noHash_2', false),
        );
    }

    /**
     * @dataProvider routerOverrideExpirationHashProvider
     */
    public function testRouterOverrideExpirationHash($routeName, $expectsHashInUrl)
    {
        $url = $this->router->generate($routeName, array('dummy' => 'dummyParam', 'expirationHash' => 'dummyHash'));
        $this->assertSame($expectsHashInUrl, $this->containsHash($url));
    }

    public function routerOverrideExpirationHashProvider()
    {
        return array(
            array('withHash_1', false),
            array('withHash_2', false),
            array('noHash_1', false),
            array('noHash_2', false),
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testExceptionOnNoCreator()
    {
        $this->router = new Router($this->getContainer(), 'routing.yml');
        $this->router->setRouteParameterName('expirationHash');
        $this->router->generate('withHash_1', array('dummy' => 'dummyParam'));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testExceptionOnNoRouteParameter()
    {
        $this->router = new Router($this->getContainer(), 'routing.yml');
        $this->router->setCreator($this->getCreator());
        $this->router->generate('withHash_1', array('dummy' => 'dummyParam'));
    }

    public function getCreator()
    {
        return new Creator(30, 'secret');
    }
    
    public function getFileHandlerMock()
    {
        $fileHandler = $this->getMockBuilder('Ticketpark\FileBundle\FileHandler\FileHandler')
            ->disableOriginalConstructor()
            ->setMethods(array('fromCache', 'cache'))
            ->getMock();

        $fileHandler->expects($this->any())
            ->method('fromCache')
            ->will($this->returnValue(false));

        $fileHandler->expects($this->any())
            ->method('cache')
            ->will($this->returnValue('foo'));

        return $fileHandler;
    }

    public function getContainer()
    {
        $container = new Container();
        $container->set('routing.loader', new YamlFileLoader(new FileLocator(__DIR__.'/Fixtures')));

        return $container;
    }

    public function containsHash($url)
    {
        $parts = explode('/', $url);
        foreach ($parts as $part) {
            $bits = explode('.', $part);
            if(count($bits) > 1){
                if($this->validateDate($bits[0])) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @link http://stackoverflow.com/a/8003798/407697
     */
    public function validateDate($date)
    {
        return preg_match('/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2})(Z|(\+|-)\d{2}(:?\d{2})?)$/', $date);
    }
}