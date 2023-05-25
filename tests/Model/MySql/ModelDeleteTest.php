<?php
declare(strict_types=1);

namespace Richbuilds\Orm\Tests\Model\MySql;

use Richbuilds\Orm\OrmException;

/**
 *
 */
class ModelDeleteTest extends MySqlTestBase
{
    /**
     * @return void
     *
     * @throws OrmException
     */
    public function testDeleteWorks(): void
    {
        $model = self::$orm->model('users')
            ->set([
                'name'=>uniqid('name', true),
                'password'=>'password',
            ])
            ->save();

        $model->delete();

        self::expectExceptionMessage('orm_test.users record not found');

        self::$orm->model('users')->fetchBy($model->getPk());
    }

    /**
     * @return void
     *
     * @throws OrmException
     */
    public function testDeleteFailsWithDependencies(): void
    {
        $model = self::$orm->model('users')
            ->set([
                'name'=>uniqid('name', true),
                'password'=>'password',
                'posts'=>[
                    ['title'=>uniqid('title', true)]
                ]
            ])
            ->save();

        self::expectExceptionMessage('Integrity constraint violation');

        $model->delete();
    }


    /**
     * @return void
     *
     * @throws OrmException
     */
    public function testDeleteFailsWithNoPk(): void
    {
        self::expectExceptionMessage('primary key not set');

        self::$orm->model('users')->delete();
    }
}
