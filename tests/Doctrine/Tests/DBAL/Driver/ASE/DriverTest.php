<?php

namespace Doctrine\Tests\DBAL\Driver\ASE;

use Doctrine\DBAL\Driver\ASE\Driver;
use Doctrine\Tests\DBAL\Driver\AbstractASEDriverTest;

class DriverTest extends AbstractASEDriverTest
{
    public function testReturnsName()
    {
        $this->assertSame('ase', $this->driver->getName());
    }

    protected function createDriver()
    {
        return new Driver();
    }
}
