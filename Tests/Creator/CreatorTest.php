<?php

namespace Ticketpark\ExpiringUrlBundle\Tests\Creator;

use Ticketpark\ExpiringUrlBundle\Creator\Creator;

class CreatorTest extends \PHPUnit_Framework_TestCase
{
   public function setUp()
   {
       $this->creator = new Creator(30, 'secret');
   }

    public function testCreate()
    {
        // The test only compares the readable date part of the returned hash
        $now = time() + 30 * 60;
        $result = $this->creator->create();
        $parts = explode('.', $result);
        $this->assertSame(date('c', $now), $parts[0]);
    }

    public function testCreateWithOverwriteDefaultTtl()
    {
        // The test only compares the readable date part of the returned hash
        $now = time() + 60 * 60;
        $result = $this->creator->create(null, 60);
        $parts = explode('.', $result);
        $this->assertSame(date('c', $now), $parts[0]);
    }

    public function testCreateWithOverwriteDefaultSecret()
    {
        // The test only compares the readable date part of the returned hash
        $now = time() + 30 * 60;
        $result = $this->creator->create(123);
        $parts = explode('.', $result);
        $this->assertSame(date('c', $now), $parts[0]);
    }
}