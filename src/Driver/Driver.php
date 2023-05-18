<?php
declare(strict_types=1);


namespace Richbuilds\Orm\Driver;

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
     */
    public function __construct(
        public readonly PDO $pdo
    )
    {
    }

    /**
     * @param string $table_name
     *
     * @return TableMeta
     *
     * @throws OrmException
     */
    abstract public function fetchTableMeta(string $table_name): TableMeta;

    /**
     * @param string $sql
     * @param array<string,mixed> $parameters
     *
     * @return PDOStatement
     */
    protected function prepareAndExec(string $sql, array $parameters = []): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($parameters);

        return $stmt;
    }

    /**
     * @param string $sql
     * @param array<string,mixed> $parameters
     *
     * @return mixed
     */
    protected function fetchSqlValue(string $sql, array $parameters = []): mixed
    {
        $stmt = $this->prepareAndExec($sql, $parameters);

        return $stmt->fetchColumn();
    }

    /**
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

}