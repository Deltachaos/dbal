<?php

namespace Doctrine\Tests\DBAL\Functional\Driver\ASE;

use Doctrine\DBAL\Driver\ASE\Driver;
use Doctrine\Tests\DBAL\Functional\Driver\AbstractASEDriverTest;

class DriverTest extends AbstractASEDriverTest
{
    protected function setUp()
    {
        if (! extension_loaded('sybase_ct')) {
            $this->markTestSkipped('sybase_ct is not installed.');
        }

        parent::setUp();

        if (! $this->_conn->getDriver() instanceof Driver) {
            $this->markTestSkipped('ase only test.');
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function createDriver()
    {
        return new Driver();
    }

    /**
     * {@inheritdoc}
     */
    protected function getDatabaseNameForConnectionWithoutDatabaseNameParameter()
    {
        return 'master';
    }
}
