<?php
declare(strict_types=1);

namespace Richbuilds\Orm\Tests\Driver\MySqlDriver;

use PDO;
use Richbuilds\Orm\Driver\MySqlDriver\MySqlDriver;
use PHPUnit\Framework\TestCase;
use Richbuilds\Orm\OrmException;

/**
 *
 */
class MySqlDriverTest extends TestCase
{

    /**
     * @return void
     *
     * @throws OrmException
     */
    public function testConstructorFailsIfNoDbSelected(): void
    {
        self::expectExceptionMessage('no database selected');

        new MySqlDriver(new PDO('mysql:host=localhost', 'test', 'test'));
    }
}
