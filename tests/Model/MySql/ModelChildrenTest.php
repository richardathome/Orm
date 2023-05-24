<?php
declare(strict_types=1);

namespace Richbuilds\Orm\Tests\Model\MySql;


use Richbuilds\Orm\OrmException;

/**
 *
 */
class ModelChildrenTest extends MySqlTestBase
{
    /**
     * @return void
     *
     * @throws OrmException
     */
    public function testSetChildrenWorks(): void
    {

        $posts = [
            [
                'title' => uniqid('title', true)
            ],
            [
                'title' => uniqid('title', true)
            ]
        ];

        $author = self::$Orm->Model('users')->set([
            'name' => uniqid('name', true),
            'password' => 'password',
            'posts' => $posts
        ]);

        self::assertIsArray($author->get('posts'));
    }


    /**
     * @return void
     * @throws OrmException
     */
    public function testSetChildrenFailsUnlessArray(): void
    {
        self::expectExceptionMessage('orm_test.users.posts: expected array got int');

        self::$Orm->Model('users')
            ->set('posts', 1);
    }


    /**
     * @return void
     *
     * @throws OrmException
     */
    public function testSetChildrenFailsWithWrongTypeChildren(): void
    {
        self::expectExceptionMessage('orm_test.posts.comments: expected comments got users');

        self::$Orm->Model('posts')->set('comments', [
            self::$Orm->Model('users')
        ]);
    }

}
