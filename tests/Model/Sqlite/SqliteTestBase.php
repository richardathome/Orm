<?php
declare(strict_types=1);


namespace Richbuilds\Orm\Tests\Model\Sqlite;

use PDO;
use PHPUnit\Framework\TestCase;
use Richbuilds\Orm\Orm;
use Richbuilds\Orm\OrmException;

/**
 *
 */
class SqliteTestBase extends TestCase
{
    protected static Orm $orm;

    /**
     * This method is called before the first test of this test class is run.
     *
     * @throws OrmException
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::$orm = new Orm(new PDO('sqlite:orm_test'));
    }
}
