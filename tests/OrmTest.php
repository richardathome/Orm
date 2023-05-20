<?php
declare(strict_types=1);

namespace Richbuilds\Orm\Tests;

use Richbuilds\Orm\Orm;
use Richbuilds\Orm\OrmException;
use Richbuilds\Orm\Tests\Model\OrmTestBase;

/**
 *
 */
class OrmTest extends OrmTestBase
{
    /**
     * @return void
     *
     * @throws OrmException
     */
    public function testConstructorFailsForUnhandledPDO(): void
    {
        self::expectExceptionMessage('unhandled driver pgsql');

        new Orm(new \PDO('pgsql:host=localhost;port=5432;dbname=orm_test', 'test', 'test'));
    }

    /**
     * @return void
     *
     * @throws OrmException
     */
    public function testModelFailsForInvalidTable(): void
    {
        self::expectExceptionMessage('table invalid-table not found in orm_test');

        self::$Orm->Model('invalid-table');
    }
}
