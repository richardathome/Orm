<?php
declare(strict_types=1);

namespace Richbuilds\Orm\Tests\Model;

use Richbuilds\Orm\OrmException;

/**
 *
 */
class ModelPkTest extends OrmTestBase
{

    /**
     * @return void
     * @throws OrmException
     */
    public function testGetPkWorksForSimplePk(): void
    {
        $model = self::$Orm->Model('users');

        self::assertEquals(null, $model->getPk());
    }


    /**
     * @return void
     * @throws OrmException
     */
    public function testGetPkWorksForCompositePk(): void
    {
        $model = self::$Orm->Model('composite_pk');

        self::assertEquals(['f1' => null, 'f2' => null], $model->getPk());
    }

    /**
     * @return void
     * @throws OrmException
     */
    public function testFetchByPkFailsForTableWithNoPk(): void {

        self::expectExceptionMessage('orm_test.no_pk has no primary key');

        self::$Orm->Model('no_pk')->fetchByPk(1);
    }

}
