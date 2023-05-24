<?php
declare(strict_types=1);

namespace Richbuilds\Orm\Tests\Driver\MySqlDriver;

use Richbuilds\Orm\Driver\MySqlDriver\MySqlQueryBuilder;
use PHPUnit\Framework\TestCase;
use Richbuilds\Orm\Model\TableMeta;

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
        $table_meta = new TableMeta('db', 'table', [], [], [], []);
        self::assertEquals(
            'SELECT * FROM `db`.`table` WHERE `db`.`table`.`id` < :id LIMIT 1;',
            $this->qb->buildFetchFirstBy($table_meta, ['id <' => 1])
        );
    }

    /**
     * @return void
     */
    public function testWhereRemovedIfEmpty(): void
    {
        $table_meta = new TableMeta('db', 'table', [], [], [], []);
        self::assertEquals(
            'SELECT * FROM `db`.`table` LIMIT 1;',
            $this->qb->buildFetchFirstBy($table_meta)
        );
    }

    /**
     * @return void
     */
    public function testPagination(): void
    {
        $table_meta = new TableMeta('db', 'table', [], [], [], []);
        self::assertEquals(
            'SELECT * FROM `db`.`table` LIMIT 10 OFFSET 10;',
            $this->qb->buildFetchAll($table_meta, [], ['per_page'=>10, 'page'=>2])
        );
    }
}
