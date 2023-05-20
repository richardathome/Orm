<?php
declare(strict_types=1);

namespace Richbuilds\Orm\Tests\Driver\SqliteDriver;

use PDO;
use Richbuilds\Orm\Driver\MySqlDriver\MySqlDriver;
use PHPUnit\Framework\TestCase;
use Richbuilds\Orm\Driver\SqliteDriver\SqliteDriver;
use Richbuilds\Orm\Driver\SqliteDriver\SqliteQueryBuilder;
use Richbuilds\Orm\OrmException;

/**
 *
 */
class SqliteDriverTest extends TestCase
{
    /**
     * @return void
     * @throws OrmException
     */
    public function testConstructorFailsWithNonMySqlPdo(): void {
        self::expectExceptionMessage('expected sqlite pdo, got mysql');

        new SqliteDriver(new PDO('mysql:host=localhost','test','test'));
    }

    /**
     * @return void
     *
     * @throws OrmException
     */
    public function testWorks(): void {

        $driver = new SqliteDriver(new PDO('sqlite:memory'));

        self::assertInstanceOf(SqliteQueryBuilder::class, $driver->QueryBuilder);
    }
}
