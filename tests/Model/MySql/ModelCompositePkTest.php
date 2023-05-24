<?php
declare(strict_types=1);

namespace Richbuilds\Orm\Tests\Model\MySql;

use Richbuilds\Orm\Model\Model;
use PHPUnit\Framework\TestCase;
use Richbuilds\Orm\OrmException;

/**
 *
 */
class ModelCompositePkTest extends MySqlTestBase
{

    /**
     * @return void
     *
     * @throws OrmException
     */
    public function testGetCompositePk(): void
    {
        $order = self::$Orm->Model('orders')->set([
            'order_id' => 1,
            'customer_id' => 1
        ]);

        self::assertEquals(['order_id' => 1, 'customer_id' => 1], $order->getPk());
    }

    /**
     * @return void
     * @throws OrmException
     */
    public function testFetchByPkWorksForCompositePk(): void
    {
        $user = self::$Orm->Model('orders')
            ->fetchByPk(['order_id' => 1, 'customer_id' => 1]);

        self::assertEquals(['order_id' => 1, 'customer_id' => 1], $user->getPk());
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
     *
     * @throws OrmException
     */
    public function testFetchByCompositePkFailsIfColumnMissing(): void
    {
        self::expectExceptionMessage('missing pk column f1');

        self::$Orm->Model('composite_pk')->fetchByPk(['foo' => 'bar']);
    }

    /**
     * @return void
     *
     * @throws OrmException
     */
    public function testFetchByPkFailsForCompositePkWithScalar(): void
    {
        self::expectExceptionMessage('orm_test.composite_pk: array expected');

        self::$Orm->Model('composite_pk')
            ->fetchByPk(1);
    }

}
