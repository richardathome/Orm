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
        $posts = $this->Orm->Query('posts', [
            'id <=' => 2
        ]);

        self::assertCount(2, $posts);

    }

    /**
     * @return void
     * @throws OrmException
     */
    public function testInClauseWorks(): void
    {
        $posts = $this->Orm->Query('posts', [
            'id IN' => [1,2]
        ]);

        self::assertCount(2, $posts);

    }

}
