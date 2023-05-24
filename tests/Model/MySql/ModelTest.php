<?php
declare(strict_types=1);

namespace Richbuilds\Orm\Tests\Model\MySql;

use Richbuilds\Orm\OrmException;

/**
 *
 */
class ModelTest extends MySqlTestBase
{
    /**
     * @return void
     *
     * @throws OrmException
     */
    public function testConstructorFailsWithInvalidTable(): void {

        self::expectExceptionMessage('table invalid-table not found in orm_test');
        self::$Orm->Model('invalid-table');
    }

}
