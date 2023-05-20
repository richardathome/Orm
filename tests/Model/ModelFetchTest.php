<?php
declare(strict_types=1);

namespace Richbuilds\Orm\Tests\Model;

use Richbuilds\Orm\OrmException;

/**
 *
 */
class ModelFetchTest extends OrmTestBase
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
        self::expectExceptionMessage('orm_test.users not found');

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
    public function testFetchParentWorks(): void
    {
        $post = self::$Orm->Model('posts')->fetchByPk(1);
        $user = $post->fetchParent('author_id');

        self::assertEquals(1, $user->get('id'));
    }


    /**
     * @return void
     */
    public function testFetchChildrenWorks(): void
    {
        self::markTestIncomplete('This test is not implemented yet.');

        /*
        $user = $this->Orm->Model('users')->fetchByPk(1);

        $posts = $user->fetchChildren('posts');

        self::assertCount(2, $posts);
        */
    }
}
