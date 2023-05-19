<?php
declare(strict_types=1);

namespace Richbuilds\Orm\Tests\Model;

use Richbuilds\Orm\OrmException;

/**
 *
 */
class ModelFetchTest extends ModelTestBase
{
    /**
     * @return void
     *
     * @throws OrmException
     */
    public function testFetchBy(): void {

        $user = $this->Orm->Model('users')
            ->fetchBy(['id'=>1]);

        self::assertEquals(1, $user->get('id'));
    }

    /**
     * @return void
     *
     * @throws OrmException
     */
    public function testFetchByFailsForInvalidColumn(): void {

        self::expectExceptionMessage('unknown column invalid-column in orm_test.users');

        $this->Orm->Model('users')
            ->fetchBy(['invalid-column'=>1]);

    }

    /**
     * @return void
     *
     * @throws OrmException
     */
    public function testFetchByFailsIfNotFound(): void {
        self::expectExceptionMessage('orm_test.users not found');

        $this->Orm->Model('users')
            ->fetchBy(['id'=>0]);
    }

}
