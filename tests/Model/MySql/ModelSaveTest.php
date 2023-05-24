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
    public function testSaveSimple(): void
    {

        $name = uniqid('name', true);

        $user = self::$Orm->Model('users')
            ->set([
                'name' => $name,
                'password' => 'foo'
            ]);

        $user->save();

        self::assertNotNull($user->getPk());

        $reload = self::$Orm->Model('users')
            ->fetchByPk($user->getPk());

        self::assertEquals($user, $reload);
    }


    /**
     * @return void
     * @throws OrmException
     */
    public function testSaveChildrenWorks(): void
    {

        $author = self::$Orm->Model('users')->set([
            'name' => uniqid('name', true),
            'password' => 'password',
            'posts' => [
                self::$Orm->Model('posts')->set('title', uniqid('title', true)),
                ['title' => uniqid('title')],
            ]
        ]);

        $author->save();

        $posts = $author->fetchChildren('posts');

        self::assertCount(2, $posts);
    }


    /**
     * @return void
     *
     * @throws OrmException
     */
    public function testUpdateWorks(): void
    {

        $original = uniqid('name', true);

        $user = self::$Orm->Model('users')
            ->set('name', $original)
            ->set('password', 'password')
            ->save();

        $pk = $user->getPk();

        $user->set('name', uniqid('name', true))
            ->save();

        self::assertEquals($pk, $user->getPk());
        self::assertNotEquals($original, $user->get('name'));
    }

    /**
     * @return void
     */
    public function testSaveRollsBackOnError(): void {

        self::markTestIncomplete();

    }
}
