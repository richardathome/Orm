<?php
declare(strict_types=1);

namespace Richbuilds\Orm\Tests\Model\MySql;

use Richbuilds\Orm\OrmException;

/**
 *
 */
class ModelSimplePkTest extends MySqlTestBase
{

    /**
     * @return void
     * @throws OrmException
     */
    public function testGetPkWorksForSimplePk(): void
    {
        $model = self::$Orm->Model('users');

        self::assertEquals(['id'=>null], $model->getPk());
    }



    /**
     * @return void
     * @throws OrmException
     */
    public function testFetchByPkFailsForTableWithNoPk(): void
    {
        self::expectExceptionMessage('orm_test.no_pk has no primary key');

        self::$Orm->Model('no_pk')->fetchByPk(1);
    }



}
