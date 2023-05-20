<?php
declare(strict_types=1);

namespace Richbuilds\Orm\Tests\Query;

use Richbuilds\Orm\OrmException;
use Richbuilds\Orm\Tests\Model\OrmTestBase;

/**
 *
 */
class QueryTest extends OrmTestBase
{

    /**
     * @return void
     *
     * @throws OrmException
     */
    public function testIteratorWorks(): void
    {
        $posts = self::$Orm->Query('posts', [
            'id <=' => 2
        ]);

        self::assertCount(2, $posts);

        foreach($posts as $key=>$post) {
            self::assertIsInt($key);
            self::assertNotEmpty($post->get('title'));
        }

    }

    /**
     * @return void
     * @throws OrmException
     */
    public function testInClauseWorks(): void
    {
        $posts = self::$Orm->Query('posts', [
            'id IN' => [1,2]
        ]);

        self::assertEquals(2, $posts->count());

    }

}
