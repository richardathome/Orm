<?php
declare(strict_types=1);

namespace Richbuilds\Orm\Tests\Driver\MySqlDriver;

use Richbuilds\Orm\Driver\MySqlDriver\MySqlQueryBuilder;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class MySqlQueryBuilderTest extends TestCase
{

    private MySqlQueryBuilder $qb;

    /**
     * This method is called before each test.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->qb = new MySqlQueryBuilder();
    }

    /**
     * @return void
     */
    public function testBuildFetchFirstBy(): void
    {
        self::assertEquals(
            'SELECT * FROM `db`.`table` WHERE `db`.`table`.`id` < :id LIMIT 1;',
            $this->qb->buildFetchFirstBy('db', 'table', ['id <' => 1])
        );
    }

    /**
     * @return void
     */
    public function testWhereRemovedIfEmpty(): void {
        self::assertEquals(
            'SELECT * FROM `db`.`table` LIMIT 1;',
            $this->qb->buildFetchFirstBy('db', 'table')
        );
    }

}
