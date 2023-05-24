<?php
declare(strict_types=1);

namespace Richbuilds\Orm\Tests;

use PDO;
use PHPUnit\Framework\TestCase;
use Richbuilds\Orm\Orm;
use Richbuilds\Orm\OrmException;

/**
 *
 */
class OrmTest extends TestCase
{
    /**
     * @return void
     *
     * @throws OrmException
     */
    public function testConstructorFailsForUnhandledPDO(): void
    {
        self::expectExceptionMessage('unhandled driver pgsql');

        new Orm(new PDO('pgsql:host=localhost;port=5432;dbname=orm_test', 'test', 'test'));
    }
}
