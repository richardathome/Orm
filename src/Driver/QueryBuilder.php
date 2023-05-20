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
    public function buildFetchFirstBy(string $database_name, string $table_name, array $conditions = []): string
    {
        $sql = sprintf('SELECT * FROM `%s`.`%s`', $database_name, $table_name);

        $sql .= $this->buildWhere($database_name, $table_name, $conditions);

        $sql .= ' LIMIT 1';

        return $sql . ';';
    }

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

            if (str_contains($column_name, ' ')) {
                [$column_name, $comparator] = explode(' ', $column_name);
            }

            if (strtoupper($comparator) === 'IN') {
                $placeholders = [];
                foreach ($value as $index => $item) {
                    $placeholder = ':' . $column_name . '_value_' . $index;
                    $placeholders[] = $placeholder;
                }
                $sql .= sprintf('`%s`.`%s`.`%s` IN (%s) AND ', $database_name, $table_name, $column_name, implode(', ', $placeholders));
            } else {
                $sql .= sprintf('`%s`.`%s`.`%s` %s :%s AND ', $database_name, $table_name, $column_name, $comparator, $column_name);
            }
        }

        $sql = substr($sql, 0, -5);

        return $sql;
    }

    /**
     * @return string
     */
    abstract public function buildFetchDatabaseName(): string;

    /**
     * @return string
     */
    abstract public function buildFetchTableMeta(): string;

    /**
     * @param string $database_name
     * @param string $table_name
     *
     * @param array<string,mixed> $conditions
     *
     * @return string
     */
    public function buildFetchAll(string $database_name, string $table_name, array $conditions): string
    {
        $sql = sprintf('SELECT * FROM `%s`.`%s`', $database_name, $table_name);

        $sql .= $this->buildWhere($database_name, $table_name, $conditions);

        return $sql . ';';

    }

}