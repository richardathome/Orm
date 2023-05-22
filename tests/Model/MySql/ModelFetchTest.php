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

        $user = self::$Orm->Model('users')
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

        self::$Orm->Model('users')
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

        self::$Orm->Model('users')
            ->fetchBy(['id' => 0]);
    }

    /**
     * @return void
     * @throws OrmException
     */
    public function testFetchByPkWorksForSimplePk(): void
    {
        $user = self::$Orm->Model('users')
            ->fetchByPk(1);

        self::assertEquals(1, $user->getPk());
    }

    /**
     * @return void
     *
     * @throws OrmException
     */
    public function testFetchByPkFailsForSimplePkWithArray(): void
    {

        self::expectExceptionMessage('orm_test.users: scalar expected');

        self::$Orm->Model('users')
            ->fetchByPk(['id' => 1]);

    }

    /**
     * @return void
     * @throws OrmException
     */
    public function testFetchByPkWorksForCompositePk(): void
    {
        $user = self::$Orm->Model('composite_pk')
            ->fetchByPk(['f1' => 1, 'f2' => 1]);

        self::assertEquals(['f1' => 1, 'f2' => 1], $user->getPk());
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


    /**
     * @return void
     * @throws OrmException
     */
    public function testFetchParentFailsForInvalidParent(): void
    {
        $post = self::$Orm->Model('posts')->fetchByPk(1);

        self::expectExceptionMessage('orm_test.posts.title is not a foreign key column');

        $post->fetchParent('title');
    }

    /**
     * @return void
     * @throws OrmException
     */
    public function testFetchParentWorks(): void
    {
        $post = self::$Orm->Model('posts')->fetchByPk(1);
        $user = $post->fetchParent('author_id');

        self::assertEquals(1, $user->get('id'));
    }


    /**
     * @return void
     *
     * @throws OrmException
     */
    public function testFetchParentFailsIfFkIsNull(): void {
        $post = self::$Orm->Model('posts');

        self::expectExceptionMessage('orm_test.posts.author_id is null');
        $post->fetchParent('author_id');
    }

    /**
     * @return void
     *
     * @throws OrmException
     */
    public function testFetchChildrenWorks(): void
    {
        $user = self::$Orm->Model('users')->fetchByPk(1);

        $posts = $user->fetchChildren('posts', ['id <=' => 2]);

        self::assertCount(2, $posts);
    }

    /**
     * @return void
     *
     * @throws OrmException
     */
    public function testFetchChildrenFailsForInvalidChildTableName(): void
    {
        $user = self::$Orm->Model('users')->fetchByPk(1);

        self::expectExceptionMessage('invalid-child-table is not a child of orm_test.users');

        $user->fetchChildren('invalid-child-table');
    }
}
