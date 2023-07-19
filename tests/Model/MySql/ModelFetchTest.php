<?php
declare(strict_types=1);

namespace Richbuilds\Orm\Tests\Model\MySql;

use Richbuilds\Orm\OrmException;

/**
 *
 */
class ModelFetchTest extends MySqlTestBase
{
    /**
     * @return void
     *
     * @throws OrmException
     */
    public function testFetchByWorks(): void
    {

        $user = self::$orm->model('users')
            ->fetchBy(['id' => 1]);

        self::assertEquals(1, $user->get('id'));
    }

    /**
     * @return void
     *
     * @throws OrmException
     */
    public function testFetchByFailsForInvalidColumn(): void
    {

        self::expectExceptionMessage('unknown column invalid-column in orm_test.users');

        self::$orm->model('users')
            ->fetchBy(['invalid-column' => 1]);
    }

    /**
     * @return void
     *
     * @throws OrmException
     */
    public function testFetchByFailsIfNotFound(): void
    {
        self::expectExceptionMessage('orm_test.users record not found');

        self::$orm->model('users')
            ->fetchBy(['id' => 0]);
    }

    /**
     * @return void
     * @throws OrmException
     */
    public function testFetchByPkWorksForSimplePk(): void
    {
        $user = self::$orm->model('users')
            ->fetchByPk(1);

        self::assertEquals(['id'=>1], $user->getPk());
    }

    /**
     * @return void
     *
     * @throws OrmException
     */
    public function testFetchChildrenWorks(): void
    {
        $user = self::$orm->model('users')->fetchByPk(1);

        $posts = $user->fetchChildren('posts', ['id <=' => 2]);

        self::assertCount(2, $posts);
    }

    /**
     * @return void
     *
     * @throws OrmException
     */
    public function testFetchChildrenFailsWithNoPk(): void
    {
        self::expectExceptionMessage('primary key not set');
        self::$orm->model('users')->fetchChildren('posts');
    }

    /**
     * @return void
     *
     * @throws OrmException
     */
    public function testFetchChildrenFailsForInvalidChildTableName(): void
    {
        $user = self::$orm->model('users')->fetchByPk(1);

        self::expectExceptionMessage('invalid-child-table is not a child of orm_test.users');

        $user->fetchChildren('invalid-child-table');
    }

    /**
     * @return void
     * @throws OrmException
     */
    public function testFetchWithInClauseWorks(): void
    {

        $user = self::$orm->model('users')->fetchBy([
            'id in'=> [1,2]
        ]);

        self::assertEquals(1, $user->get('id'));
    }
}
