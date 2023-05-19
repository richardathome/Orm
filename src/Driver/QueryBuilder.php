<?php
declare(strict_types=1);


namespace Richbuilds\Orm\Driver;

/**
 *
 */
abstract class QueryBuilder
{

    /**
     * @param string $database_name
     * @param string $table_name
     * @param array<string,mixed> $conditions
     *
     * @return string
     */
    abstract public function buildFetchFirstBy(string $database_name, string $table_name, array $conditions): string;

    /**
     * @param string $database_name
     * @param string $table_name
     * @param array<string,mixed> $conditions
     *
     * @return string
     */
    protected function buildWhere(string $database_name, string $table_name, array $conditions = []): string
    {
        if (empty($conditions)) {
            return '';
        }

        $sql = ' WHERE ';

        foreach ($conditions as $column_name => $value) {
            $comparator = '=';

            $sql .= sprintf('`%s`.`%s`.`%s` %s :%s AND ', $database_name, $table_name, $column_name, $comparator, $column_name);
        }

        $sql = substr($sql, 0, -5);

        return $sql;
    }

}