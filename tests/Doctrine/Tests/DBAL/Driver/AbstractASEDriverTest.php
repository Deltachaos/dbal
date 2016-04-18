<?php

namespace Doctrine\Tests\DBAL\Driver;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\ASE\ASEDriverException;
use Doctrine\DBAL\Platforms\ASE150Platform;
use Doctrine\DBAL\Schema\ASESchemaManager;

class AbstractASEDriverTest extends AbstractDriverTest
{
    protected function createDriver()
    {
        return $this->getMockForAbstractClass('Doctrine\DBAL\Driver\AbstractASEDriver');
    }

    protected function createPlatform()
    {
        return new ASE150Platform();
    }

    protected function createSchemaManager(Connection $connection)
    {
        return new ASESchemaManager($connection);
    }

    protected function getExceptionConversionData()
    {
        return array(
            self::EXCEPTION_UNIQUE_CONSTRAINT_VIOLATION => array(
                array(16, null, "Attempt to insert duplicate key row in object 'duplicatekey_table' with unique index 'duplicatek_5760020521'")
            ),
            self::EXCEPTION_TABLE_NOT_FOUND => array(
                array(16, null, "unknown_table not found. Specify owner.objectname or use sp_help to check whether the object exists (sp_help may produce lots of output).")
            ),
            self::EXCEPTION_TABLE_EXISTS => array(
                array(16, null, "There is already an object named 'alreadyexist_table' in the database.")
            ),
            self::EXCEPTION_FOREIGN_KEY_CONSTRAINT_VIOLATION => array(
                array(16, null, "Foreign key constraint violation occurred, dbname =  'test', table name = 'owning_table', constraint name = 'FK_8F4773C1E3087FFC'."),
                array(16, null, "Dependent foreign key constraint violation in a referential integrity constraint. dbname =  'test', table name = 'constraint_error_table', constraint name = 'FK_8F4773C1E3087FFC'."),
                array(16, null, "Dependent foreign key constraint violation in a referential integrity constraint. dbname =  'test', table name = 'constraint_error_table', constraint name = 'FK_8F4773C1E3087FFC'."),
                array(16, null, "Could not truncate table 'constraint_error_table' because there are referential constraints defined on it and there are data rows in some of the referencing tables.")
            ),
            self::EXCEPTION_NOT_NULL_CONSTRAINT_VIOLATION => array(
                array(16, null, "The column value in table notnull_table does not allow null values.")
            ),
            self::EXCEPTION_INVALID_FIELD_NAME => array(
                array(16, null, "Invalid column name 'name'.")
            ),
            self::EXCEPTION_NON_UNIQUE_FIELD_NAME => array(
                array(16, null, "Ambiguous column name id")
            ),
            self::EXCEPTION_SYNTAX_ERROR => array(
                array(16, null, "Incorrect syntax near 'syntax_error_table'.")
            ),
            self::EXCEPTION_CONNECTION => array(
                array(16, null, "Sybase:  Unable to connect ")
            )
        );
    }

    protected function getDatabasePlatformsForVersions()
    {
        return array(
            array('Adaptive Server Enterprise/12/SWR 9613 GA/P/HP9000-829/HP-UX 11.0 /main/1647/32-bit/FBO/Sat Jun  2 02:14:45 2001', 'Doctrine\DBAL\Platforms\ASEPlatform'),
            array('Adaptive Server Enterprise/12', 'Doctrine\DBAL\Platforms\ASEPlatform'),
            array('Adaptive Server Enterprise/12.5/SWR 9613 GA/P/HP9000-829/HP-UX 11.0 /main/1647/32-bit/FBO/Sat Jun  2 02:14:45 2001', 'Doctrine\DBAL\Platforms\ASEPlatform'),
            array('Adaptive Server Enterprise/12.5', 'Doctrine\DBAL\Platforms\ASEPlatform'),
            array('Adaptive Server Enterprise/12.5.4/EBF 15432 ESD#8/P/Sun_svr4/OS 5.8/ase1254/2105/64-bit/FBO/Sat Mar 22 14:38:37 2008', 'Doctrine\DBAL\Platforms\ASEPlatform'),
            array('Adaptive Server Enterprise/12.5.4', 'Doctrine\DBAL\Platforms\ASEPlatform'),
            array('Adaptive Server Enterprise/12.5.0.1/SWR 9981 IR/P/Sun_svr4/OS 5.8/rel12501/1776/32-bit/FBO/Mon Feb 25 23:35:46 2002', 'Doctrine\DBAL\Platforms\ASEPlatform'),
            array('Adaptive Server Enterprise/12.5.0.1', 'Doctrine\DBAL\Platforms\ASEPlatform'),

            array('Adaptive Server Enterprise/15/EBF 17690 ESD#1.1 RELSE/P/Solaris AMD64/OS 5.10/ase1503/2681/64-bit/FBO/Thu Aug 20 15:37:40 2009', 'Doctrine\DBAL\Platforms\ASE150Platform'),
            array('Adaptive Server Enterprise/15', 'Doctrine\DBAL\Platforms\ASE150Platform'),
            array('Adaptive Server Enterprise/15.0/EBF 17690 ESD#1.1 RELSE/P/Solaris AMD64/OS 5.10/ase1503/2681/64-bit/FBO/Thu Aug 20 15:37:40 2009', 'Doctrine\DBAL\Platforms\ASE150Platform'),
            array('Adaptive Server Enterprise/15.0', 'Doctrine\DBAL\Platforms\ASE150Platform'),
            array('Adaptive Server Enterprise/15.0.3/EBF 17690 ESD#1.1 RELSE/P/Solaris AMD64/OS 5.10/ase1503/2681/64-bit/FBO/Thu Aug 20 15:37:40 2009', 'Doctrine\DBAL\Platforms\ASE150Platform'),
            array('Adaptive Server Enterprise/15.0.3', 'Doctrine\DBAL\Platforms\ASE150Platform'),
            array('Adaptive Server Enterprise/15.0.3.0/EBF 17690 ESD#1.1 RELSE/P/Solaris AMD64/OS 5.10/ase1503/2681/64-bit/FBO/Thu Aug 20 15:37:40 2009', 'Doctrine\DBAL\Platforms\ASE150Platform'),
            array('Adaptive Server Enterprise/15.0.3.0', 'Doctrine\DBAL\Platforms\ASE150Platform'),

            array('Adaptive Server Enterprise/15.7/EBF 23817 SMP SP132 /P/Solaris AMD64/OS 5.10/ase157sp131x/3919/64-bit/FBO/Tue Jan 20 04:56:45 2015', 'Doctrine\DBAL\Platforms\ASE157Platform'),
            array('Adaptive Server Enterprise/15.7', 'Doctrine\DBAL\Platforms\ASE157Platform'),
            array('Adaptive Server Enterprise/15.7.0/EBF 23817 SMP SP132 /P/Solaris AMD64/OS 5.10/ase157sp131x/3919/64-bit/FBO/Tue Jan 20 04:56:45 2015', 'Doctrine\DBAL\Platforms\ASE157Platform'),
            array('Adaptive Server Enterprise/15.7.0', 'Doctrine\DBAL\Platforms\ASE157Platform'),
            array('Adaptive Server Enterprise/15.7.0.0/EBF 23817 SMP SP132 /P/Solaris AMD64/OS 5.10/ase157sp131x/3919/64-bit/FBO/Tue Jan 20 04:56:45 2015', 'Doctrine\DBAL\Platforms\ASE157Platform'),
            array('Adaptive Server Enterprise/15.7.0.0', 'Doctrine\DBAL\Platforms\ASE157Platform'),

            array('Adaptive Server Enterprise/16 SP02 GA/EBF 23817 SMP SP132 /P/Solaris AMD64/OS 5.10/ase157sp131x/3919/64-bit/FBO/Tue Jan 20 04:56:45 2015', 'Doctrine\DBAL\Platforms\ASE160Platform'),
            array('Adaptive Server Enterprise/16 SP02 GA', 'Doctrine\DBAL\Platforms\ASE160Platform'),
            array('Adaptive Server Enterprise/16 GA/EBF 23817 SMP SP132 /P/Solaris AMD64/OS 5.10/ase157sp131x/3919/64-bit/FBO/Tue Jan 20 04:56:45 2015', 'Doctrine\DBAL\Platforms\ASE160Platform'),
            array('Adaptive Server Enterprise/16 GA', 'Doctrine\DBAL\Platforms\ASE160Platform'),
            array('Adaptive Server Enterprise/16/EBF 23817 SMP SP132 /P/Solaris AMD64/OS 5.10/ase157sp131x/3919/64-bit/FBO/Tue Jan 20 04:56:45 2015', 'Doctrine\DBAL\Platforms\ASE160Platform'),
            array('Adaptive Server Enterprise/16', 'Doctrine\DBAL\Platforms\ASE160Platform'),

            array('Adaptive Server Enterprise/16.0 SP02 GA/EBF 24953 SMP/P/Solaris AMD64/OS 5.10/asecorona/2424/64-bit/FBO/Tue Aug 11 04:40:34 2015', 'Doctrine\DBAL\Platforms\ASE160Platform'),
            array('Adaptive Server Enterprise/16.0 SP02 GA', 'Doctrine\DBAL\Platforms\ASE160Platform'),
            array('Adaptive Server Enterprise/16.0 GA/EBF 24953 SMP/P/Solaris AMD64/OS 5.10/asecorona/2424/64-bit/FBO/Tue Aug 11 04:40:34 2015', 'Doctrine\DBAL\Platforms\ASE160Platform'),
            array('Adaptive Server Enterprise/16.0 GA', 'Doctrine\DBAL\Platforms\ASE160Platform'),
            array('Adaptive Server Enterprise/16.0/EBF 23817 SMP SP132 /P/Solaris AMD64/OS 5.10/ase157sp131x/3919/64-bit/FBO/Tue Jan 20 04:56:45 2015', 'Doctrine\DBAL\Platforms\ASE160Platform'),
            array('Adaptive Server Enterprise/16.0', 'Doctrine\DBAL\Platforms\ASE160Platform'),

            array('Adaptive Server Enterprise/16.0.0 SP02 GA/EBF 24953 SMP/P/Solaris AMD64/OS 5.10/asecorona/2424/64-bit/FBO/Tue Aug 11 04:40:34 2015', 'Doctrine\DBAL\Platforms\ASE160Platform'),
            array('Adaptive Server Enterprise/16.0.0 SP02 GA', 'Doctrine\DBAL\Platforms\ASE160Platform'),
            array('Adaptive Server Enterprise/16.0.0 GA/EBF 24953 SMP/P/Solaris AMD64/OS 5.10/asecorona/2424/64-bit/FBO/Tue Aug 11 04:40:34 2015', 'Doctrine\DBAL\Platforms\ASE160Platform'),
            array('Adaptive Server Enterprise/16.0.0 GA', 'Doctrine\DBAL\Platforms\ASE160Platform'),
            array('Adaptive Server Enterprise/16.0.0/EBF 23817 SMP SP132 /P/Solaris AMD64/OS 5.10/ase157sp131x/3919/64-bit/FBO/Tue Jan 20 04:56:45 2015', 'Doctrine\DBAL\Platforms\ASE160Platform'),
            array('Adaptive Server Enterprise/16.0.0', 'Doctrine\DBAL\Platforms\ASE160Platform'),

            array('Adaptive Server Enterprise/16.0.0.0 SP02 GA/EBF 24953 SMP/P/Solaris AMD64/OS 5.10/asecorona/2424/64-bit/FBO/Tue Aug 11 04:40:34 2015', 'Doctrine\DBAL\Platforms\ASE160Platform'),
            array('Adaptive Server Enterprise/16.0.0.0 SP02 GA', 'Doctrine\DBAL\Platforms\ASE160Platform'),
            array('Adaptive Server Enterprise/16.0.0.0 GA/EBF 24953 SMP/P/Solaris AMD64/OS 5.10/asecorona/2424/64-bit/FBO/Tue Aug 11 04:40:34 2015', 'Doctrine\DBAL\Platforms\ASE160Platform'),
            array('Adaptive Server Enterprise/16.0.0.0 GA', 'Doctrine\DBAL\Platforms\ASE160Platform'),
            array('Adaptive Server Enterprise/16.0.0.0/EBF 23817 SMP SP132 /P/Solaris AMD64/OS 5.10/ase157sp131x/3919/64-bit/FBO/Tue Jan 20 04:56:45 2015', 'Doctrine\DBAL\Platforms\ASE160Platform'),
            array('Adaptive Server Enterprise/16.0.0.0', 'Doctrine\DBAL\Platforms\ASE160Platform')
        );
    }
}
