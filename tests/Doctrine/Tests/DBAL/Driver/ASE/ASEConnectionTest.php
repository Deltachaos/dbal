<?php

namespace Doctrine\Tests\DBAL\Driver\ASE;

use Doctrine\Tests\DbalTestCase;

class ASEConnectionTest extends DbalTestCase
{
    /**
     * The sqlsrv driver connection mock under test.
     *
     * @var \Doctrine\DBAL\Driver\ASE\ASEConnection|\PHPUnit_Framework_MockObject_MockObject
     */
    private $connectionMock;

    protected function setUp()
    {
        if ( ! extension_loaded('sybase_ct')) {
            $this->markTestSkipped('sybase_ct is not installed.');
        }

        parent::setUp();

        $this->connectionMock = $this->getMockBuilder('Doctrine\DBAL\Driver\ASE\ASEConnection')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
    }

    public function testDoesNotRequireQueryForServerVersion()
    {
        $this->assertTrue($this->connectionMock->requiresQueryForServerVersion());
    }
}
