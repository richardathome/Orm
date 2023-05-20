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
     * @param QueryBuilder $QueryBuilder
     */
    public function __construct(
        public readonly PDO          $pdo,
        public readonly QueryBuilder $QueryBuilder
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
     * @param string $database_name
     * @param string $table_name
     * @param array<string,mixed> $conditions
     * @param array<string,mixed> $pagination
     *
     * @return PDOStatement
     */
    public function fetchQueryIteratorStmt(string $database_name, string $table_name, array $conditions = [], array $pagination = []): PDOStatement
    {
        $sql = $this->QueryBuilder->buildFetchAll($database_name, $table_name, $conditions, $pagination);

        return $this->prepareAndExec($sql, $conditions);
    }

    /**
     * @param string $database_name
     * @param string $table_name
     * @param array<string,mixed> $conditions
     *
     * @return array<string,mixed>|false
     */
    public function fetchFirstBy(string $database_name, string $table_name, array $conditions): array|false
    {
        $sql = $this->QueryBuilder->buildFetchFirstBy($database_name, $table_name, $conditions);

        $stmt = $this->prepareAndExec($sql, $conditions);

        return $stmt->fetch(PDO::FETCH_ASSOC);

    }

    /**
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

                if (!is_array($value)) {
                    $value = [$value];
                }

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
     * @param string $sql
     * @param array<string,mixed> $parameters
     *
     * @return mixed
     */
    protected function fetchSqlColumn(string $sql, array $parameters = []): mixed
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