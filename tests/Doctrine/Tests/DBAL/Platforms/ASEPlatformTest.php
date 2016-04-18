<?php

namespace Doctrine\Tests\DBAL\Platforms;

use Doctrine\Tests\DbalTestCase;

class ASEPlatformTest extends DbalTestCase
{
    private $platform;

    public function setUp()
    {
        $this->platform = new \Doctrine\DBAL\Platforms\ASEPlatform();
    }

    public function testCreateWithoutDevices()
    {
        $platform = new \Doctrine\DBAL\Platforms\ASEPlatform();


        $this->assertEquals(
            $platform->wrapByMasterContext("CREATE DATABASE test"),
            $platform->getCreateDatabaseSQL('test')
        );
    }
}

