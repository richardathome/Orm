<?php
declare(strict_types=1);

namespace Richbuilds\Orm\Tests;

use Richbuilds\Orm\OrmException;
use Richbuilds\Orm\Tests\Model\OrmTestBase;

/**
 *
 */
class OrmTest extends OrmTestBase
{
    /**
     * @return void
     *
     * @throws OrmException
     */
    public function testModelFailsForInvalidTable(): void
    {
        self::expectExceptionMessage('table invalid-table not found in orm_test');

        $this->Orm->Model('invalid-table');
    }
}
