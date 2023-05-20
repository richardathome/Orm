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

        $user = $this->Orm->Model('users')
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

        $this->Orm->Model('users')
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

        $this->Orm->Model('users')
            ->fetchBy(['id' => 0]);
    }

    /**
     * @return void
     * @throws OrmException
     */
    public function testFetchByPkWorksForSimplePk(): void
    {
        $user = $this->Orm->Model('users')
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

        $this->Orm->Model('users')
            ->fetchByPk(['id' => 1]);

    }

    /**
     * @return void
     * @throws OrmException
     */
    public function testFetchByPkWorksForCompositePk(): void
    {
        $user = $this->Orm->Model('composite_pk')
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

        $this->Orm->Model('composite_pk')
            ->fetchByPk(1);


    }

    /**
     * @return void
     * @throws OrmException
     */
    public function testFetchParentWorks(): void
    {
        $post = $this->Orm->Model('posts')->fetchByPk(1);
        $user = $post->fetchParent('author_id');

        self::assertEquals(1, $post->get('id'));
    }
}
