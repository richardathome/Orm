<?php
declare(strict_types=1);


namespace Richbuilds\Orm\Driver\SqliteDriver;

use PDO;
use Richbuilds\Orm\Driver\Driver;
use Richbuilds\Orm\Model\TableMeta;
use Richbuilds\Orm\OrmException;

/**
 *
 */
class SqliteDriver extends Driver
{

    /**
     * In request storage of any table metadata fetched
     *
     * @var array<string,TableMeta> $table_meta_cache
     */
    private mixed $table_meta_cache = [];

    /**
     * @param PDO $pdo
     *
     * @throws OrmException
     */
    public function __construct(
        PDO $pdo
    )
    {
        $this->guardValidDriver($pdo,'sqlite');

        parent::__construct($pdo, new SqliteQueryBuilder());

    }

    /**
     * @inheritDoc
     */
    public function fetchTableMeta(string $table_name): TableMeta
    {
        if (!isset($this->table_meta_cache[$table_name])) {

            $sql = $this->QueryBuilder->buildFetchTableMeta();
            $rows = $this->fetchSqlAll($sql,[
                'table_name'=>$table_name
            ]);

            if (empty($rows)) {
                throw new OrmException(sprintf('table %s not found',$table_name));
            }

            return new TableMeta('',$table_name,[],[],[],[]);
        }

        return $this->table_meta_cache[$table_name];
    }


    /**
     * @inheritDoc
     */
    public function insert(string $database_name, string $table_name, array $values = [], array $conditions = []): bool|string
    {
        // TODO: Implement insert() method.
        return false;

    }

    /**
     * @inheritDoc
     */
    public function update(string $database_name, string $table_name, array $values = [], array $conditions = []): int
    {
        // TODO: Implement update() method.
        return 0;
    }
}