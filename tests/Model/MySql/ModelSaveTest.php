<?php
declare(strict_types=1);

namespace Richbuilds\Orm\Tests\Model\MySql;

use Richbuilds\Orm\OrmException;

/**
 *
 */
class ModelSaveTest extends MySqlTestBase
{

    /**
     * @return void
     *
     * @throws OrmException
     */
    public function testSaveSimple(): void {

        $name = uniqid('name',true);

        $user = self::$Orm->Model('users')
            ->set([
                'name'=>$name,
                'password'=>'foo'
            ]);

        $user->save();

        self::assertNotNull($user->getPk());

        $reload = self::$Orm->Model('users')
            ->fetchByPk($user->getPk());

        self::assertEquals($user,$reload);
    }


}
