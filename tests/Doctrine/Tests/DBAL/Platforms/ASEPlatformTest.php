<?php

namespace Doctrine\Tests\DBAL\Platforms;

use Doctrine\Tests\DbalTestCase;

/**
 * @group DBAL-222
 */
class ASEPlatformTest extends DbalTestCase
{
    private $platform;

    public function setUp()
    {
        $this->platform = new \Doctrine\DBAL\Platforms\ASEPlatform();
    }

    /*
    public function testCreateDevicesDataSizeIdentifierAndLogSize()
    {
        $platform = new \Doctrine\DBAL\Platforms\ASEPlatform(array(
            'devices' => array(
                'data' => array(
                    array(
                        'name' => 'data00',
                        'size' => '123G'
                    ),
                    array(
                        'name' => 'data01',
                        'size' => 456
                    )
                ),
                'log' => array(
                    array(
                        'name' => 'log00',
                        'size' => 654
                    ),
                    array(
                        'name' => 'log01',
                        'size' => '321M'
                    )
                )
            )
        ));


        $this->assertEquals(
            $platform->wrapByMasterContext("CREATE DATABASE test ON data00='123G', data01=456 LOG ON log00=654, log01='321M'"),
            $platform->getCreateDatabaseSQL('test')
        );
    }

    public function testCreateDevicesDataSizeAndLogSize()
    {
        $platform = new \Doctrine\DBAL\Platforms\ASEPlatform(array(
            'devices' => array(
                'data' => array(
                    array(
                        'name' => 'data00',
                        'size' => 123
                    ),
                    array(
                        'name' => 'data01',
                        'size' => 456
                    )
                ),
                'log' => array(
                    array(
                        'name' => 'log00',
                        'size' => 654
                    ),
                    array(
                        'name' => 'log01',
                        'size' => 321
                    )
                )
            )
        ));


        $this->assertEquals(
            $platform->wrapByMasterContext("CREATE DATABASE test ON data00=123, data01=456 LOG ON log00=654, log01=321"),
            $platform->getCreateDatabaseSQL('test')
        );
    }

    public function testCreateDevicesDataSizeAndLog()
    {
        $platform = new \Doctrine\DBAL\Platforms\ASEPlatform(array(
            'devices' => array(
                'data' => array(
                    array(
                        'name' => 'data00',
                        'size' => 123
                    ),
                    array(
                        'name' => 'data01',
                        'size' => 456
                    )
                ),
                'log' => array(
                    array(
                        'name' => 'log00'
                    ),
                    array(
                        'name' => 'log01'
                    )
                )
            )
        ));


        $this->assertEquals(
            $platform->wrapByMasterContext("CREATE DATABASE test ON data00=123, data01=456 LOG ON log00, log01"),
            $platform->getCreateDatabaseSQL('test')
        );
    }

    public function testCreateDevicesDataAndLog()
    {
        $platform = new \Doctrine\DBAL\Platforms\ASEPlatform(array(
            'devices' => array(
                'data' => array(
                    array(
                        'name' => 'data00'
                    ),
                    array(
                        'name' => 'data01'
                    )
                ),
                'log' => array(
                    array(
                        'name' => 'log00'
                    ),
                    array(
                        'name' => 'log01'
                    )
                )
            )
        ));


        $this->assertEquals(
            $platform->wrapByMasterContext("CREATE DATABASE test ON data00, data01 LOG ON log00, log01"),
            $platform->getCreateDatabaseSQL('test')
        );
    }

    public function testCreateDevicesDataSize()
    {
        $platform = new \Doctrine\DBAL\Platforms\ASEPlatform(array(
            'devices' => array(
                'data' => array(
                    array(
                        'name' => 'data00'
                    ),
                    array(
                        'name' => 'data01',
                        'size' => 456
                    )
                )
            )
        ));


        $this->assertEquals(
            $platform->wrapByMasterContext("CREATE DATABASE test ON data00, data01=456"),
            $platform->getCreateDatabaseSQL('test')
        );
    }

    public function testCreateDevicesData()
    {
        $platform = new \Doctrine\DBAL\Platforms\ASEPlatform(array(
            'devices' => array(
                'data' => array(
                    array(
                        'name' => 'data00'
                    )
                )
            )
        ));


        $this->assertEquals(
            $platform->wrapByMasterContext("CREATE DATABASE test ON data00"),
            $platform->getCreateDatabaseSQL('test')
        );
    }

    public function testCreateDevicesLog()
    {
        $platform = new \Doctrine\DBAL\Platforms\ASEPlatform(array(
            'devices' => array(
                'log' => array(
                    array(
                        'name' => 'data00'
                    )
                )
            )
        ));


        $this->assertEquals(
            $platform->wrapByMasterContext("CREATE DATABASE test LOG ON data00"),
            $platform->getCreateDatabaseSQL('test')
        );
    }

    public function testCreateWithoutDevices()
    {
        $platform = new \Doctrine\DBAL\Platforms\ASEPlatform();


        $this->assertEquals(
            $platform->wrapByMasterContext("CREATE DATABASE test"),
            $platform->getCreateDatabaseSQL('test')
        );
    }
    */
}

