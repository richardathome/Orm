<?php
declare(strict_types=1);

namespace Richbuilds\Orm\Tests\Model\MySql;

use DateTime;
use Richbuilds\Orm\Model\Model;
use Richbuilds\Orm\OrmException;

/**
 *
 */
class ModelParentTest extends MySqlTestBase
{

    /**
     * @dataProvider setParentProvider
     *
     * @param mixed $value
     * @param string $expected
     * @param string $expected_error
     *
     * @return void
     *
     * @throws OrmException
     */
    public function testSetParent(mixed $value, string $expected, string $expected_error): void
    {

        if (!empty($expected_error)) {
            self::expectExceptionMessage($expected_error);
        }

        $post = self::$Orm->Model('posts')
            ->set([
                'title' => uniqid('title', true),
                'author_id' => $value
            ]);

        if (empty($expected_error)) {
            self::assertEquals($expected, get_debug_type($post->get('author_id')));
        }
    }

    /**
     * @return array<array{mixed,string,string}>
     *
     * @throws OrmException
     */
    public static function setParentProvider(): array
    {

        self::setUpBeforeClass();

        $valid_values = [
            'name' => uniqid('name', true),
            'password' => 'password'
        ];

        $invalid_values = [
            'name' => new DateTime(),
            'password' => 'password'
        ];

        $valid_model = self::$Orm->Model('users')->set($valid_values);

        return [
            [1, 'int', ''],
            [0, '', 'orm_test.posts.author_id: orm_test.users record not found'],
            [$valid_values, Model::class, ''],
            [$valid_model, Model::class, ''],
            [$invalid_values, '', 'orm_test.users.name: expected varchar(45), got DateTime']
        ];
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
     * @throws OrmException
     */
    public function testSaveWithParentValue(): void
    {
        $post = self::$Orm->Model('posts')->set([
            'title' => uniqid('title', true),
            'author_id' => 1
        ])
            ->save();

        self::assertEquals(1, $post->get('author_id'));
    }

    /**
     * @return void
     *
     * @throws OrmException
     */
    public function testSaveWithParentArrayCreatesParentAndForeignKey(): void
    {
        $author = [
            'name' => uniqid('name', true),
            'password' => 'password'
        ];

        $post = self::$Orm->Model('posts')->set([
            'title' => uniqid('title', true),
            'author_id' => $author
        ]);

        $post->save();

        self::assertIsNumeric($post->get('author_id'));

        $reload = $post->fetchParent('author_id');
        self::assertEquals($author['name'], $reload->get('name'));

        // NOTE: $author['id'] IS NOT set
    }


    /**
     * @return void
     *
     * @throws OrmException
     */
    public function testSaveWithParentModelCreatesParentAndForeignKey(): void
    {
        $author = self::$Orm->Model('users')->set([
            'name' => uniqid('name', true),
            'password' => 'password'
        ]);

        $post = self::$Orm->Model('posts')->set([
            'title' => uniqid('title', true),
            'author_id' => $author
        ])
            ->save();

        // confirm $author object is $post has been replaced by a fk
        self::assertIsNumeric($post->get('author_id'));

        $reload = $post->fetchParent('author_id');

        self::assertEquals($author->getPk(), $reload->getPk());

        // NOTE: $author primary key IS set
    }


    /**
     * @return void
     * @throws OrmException
     */
    public function testSetParentFailsForInvalidParentModel(): void
    {

        $comment = self::$Orm->Model('comments');

        self::expectExceptionMessage('orm_test.posts.author_id: expected users, got comments');

        self::$Orm->Model('posts')
            ->set('author_id', $comment);

    }



}
