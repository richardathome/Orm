<?php
declare(strict_types=1);

namespace Richbuilds\Orm\Tests\Model\MySql;

use Richbuilds\Orm\OrmException;

/**
 *
 */
class ModelPkTest extends MySqlTestBase
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
    public function testGetPkWorksForCompositePk(): void
    {
        $model = self::$Orm->Model('composite_pk')->set([
            'f1' => 1,
            'f2' => 2
        ]);

        self::assertEquals(['f1' => 1, 'f2' => 2], $model->getPk());
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


    /**
     * @return void
     *
     * @throws OrmException
     */
    public function testFetchByCompositePkFailsIfColumnMissing(): void
    {
        self::expectExceptionMessage('missing pk column f1');

        self::$Orm->Model('composite_pk')->fetchByPk(['foo' => 'bar']);
    }

}
