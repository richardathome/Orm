<?php
declare(strict_types=1);


namespace Richbuilds\Orm\Driver;

use RuntimeException;
use PDO;
use PDOStatement;
use Richbuilds\Orm\Model\TableMeta;
use Richbuilds\Orm\OrmException;

/**
 * Responsible for communicating with a PDO datasource
 */
abstract class Driver
{
    /**
     * @param PDO $pdo
     * @param QueryBuilder $QueryBuilder
     */
    public function __construct(
        public readonly PDO          $pdo,
        public readonly QueryBuilder $QueryBuilder
    ) {
    }


    /**
     * Returns the query to fetch the metadata for all the columns in
     * $database_name.$table_name along with associated foreign key metadata
     *
     * @param string $table_name
     *
     * @return TableMeta
     *
     * @throws OrmException
     */
    abstract public function fetchTableMeta(string $table_name): TableMeta;


    /**
     * Inserts values into $database_name.$table_name using $conditions
     *
     * @param TableMeta $table_meta
     * @param array<string,mixed> $values
     * @param array<string,mixed> $conditions
     *
     * @return bool|string false on failure
     */
    abstract public function insert(TableMeta $table_meta, array $values = [], array $conditions = []): bool|string;


    /**
     * Updates $database_name.$table_name with $values using $conditions
     *
     * @param TableMeta $table_meta
     * @param array<string,mixed> $values
     * @param array<string,mixed> $conditions
     *
     * @return int
     */
    abstract public function update(TableMeta $table_meta, array $values = [], array $conditions = []): int;

    /**
     * @param TableMeta $table_meta
     *
     * @param array<string,mixed> $conditions
     *
     * @return void
     */
    abstract public function delete(TableMeta $table_meta, array $conditions): void;

    /**
     * Ensure $pdo is the correct driver type
     *
     * @param PDO $pdo
     * @param string $expected_driver_name
     *
     * @return void
     *
     * @throws OrmException
     */
    protected function guardValidDriver(PDO $pdo, string $expected_driver_name): void
    {
        $driver_name = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver_name !== $expected_driver_name) {
            throw new OrmException(sprintf('expected %s pdo, got %s', $expected_driver_name, $driver_name));
        }
    }


    /**
     * Returns a PDO statement used by Query to lazy load a result set
     *
     * @param TableMeta $table_meta
     * @param array<string,mixed> $conditions
     * @param array<string,mixed> $pagination
     *
     * @return PDOStatement
     *
     */
    public function fetchQueryIteratorStmt(
        TableMeta $table_meta,
        array     $conditions = [],
        array     $pagination = []
    ): PDOStatement {
        $sql = $this->QueryBuilder->buildFetchAll($table_meta, $conditions, $pagination);

        return $this->prepareAndExec($sql, $conditions);
    }


    /**
     * Returns the first row in $database_name.$table_name that matches
     * $conditions or false if no matching row is found
     *
     * @param TableMeta $table_meta
     * @param array<string,mixed> $conditions
     *
     * @return array<string,mixed>|false
     *
     */
    public function fetchFirstBy(TableMeta $table_meta, array $conditions): array|false
    {
        $sql = $this->QueryBuilder->buildFetchFirstBy($table_meta, $conditions);

        $stmt = $this->prepareAndExec($sql, $conditions);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }


    /**
     * Prepares $sql and executes it with $parameters
     *
     * @param string $sql
     * @param array<string,mixed> $parameters
     *
     * @return PDOStatement
     */
    protected function prepareAndExec(string $sql, array $parameters = []): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);

        // bind the params
        foreach ($parameters as $name => $value) {
            $comparator = '=';

            if (str_contains($name, ' ')) {
                [$name, $comparator] = explode(' ', $name);
            }

            if ($comparator === 'IN') {
                $value = is_array($value) ? $value : [$value];

                foreach ($value as $k => $v) {
                    $stmt->bindValue(':' . $name . '_value_' . $k, $v);
                }
            } else {
                $stmt->bindValue(':' . $name, $value);
            }
        }

        $stmt->execute();

        return $stmt;
    }


    /**
     * Fetches a single value
     *
     * @param string $sql
     * @param array<string,mixed> $parameters
     *
     * @return mixed
     *
     */
    protected function fetchSqlColumn(string $sql, array $parameters = []): mixed
    {
        $stmt = $this->prepareAndExec($sql, $parameters);

        return $stmt->fetchColumn();
    }


    /**
     * Fetches all the rows as an array
     *
     * @param string $sql
     * @param array<string,mixed> $parameters
     *
     * @return array<int,array<string,mixed>>
     */
    protected function fetchSqlAll(string $sql, array $parameters = []): array
    {
        $stmt = $this->prepareAndExec($sql, $parameters);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    /**
     * Begins a transaction
     *
     * @return void
     *
     * @throws OrmException
     */
    public function beginTransaction(): void
    {
        if ($this->pdo->inTransaction()) {
            throw new OrmException('already in transaction');
        }

        $this->pdo->beginTransaction();
    }


    /**
     * Commits the current transaction
     *
     * @return void
     */
    public function commitTransaction(): void
    {
        if (!$this->pdo->inTransaction()) {
            throw new RuntimeException('not in transaction');
        }

        $this->pdo->commit();
    }


    /**
     * Rolls back the current transaction
     *
     * @return void
     */
    public function rollbackTransaction(): void
    {
        if (!$this->pdo->inTransaction()) {
            throw new RuntimeException('not in transaction');
        }

        $this->pdo->rollBack();
    }
}
