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

        $where = ' WHERE ';

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
                $where .= sprintf('`%s`.`%s`.`%s` IN (%s) AND ', $database_name, $table_name, $column_name, implode(', ', $placeholders));
            } else {
                $where .= sprintf('`%s`.`%s`.`%s` %s :%s AND ', $database_name, $table_name, $column_name, $comparator, $column_name);
            }
        }

        $where = substr($where, 0, -5);

        return $where;
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
     * @param array<string,mixed> $pagination
     *
     * @return string
     */
    public function buildFetchAll(string $database_name, string $table_name, array $conditions = [], array $pagination = []): string
    {
        $sql = sprintf('SELECT * FROM `%s`.`%s`', $database_name, $table_name);

        $sql .= $this->buildWhere($database_name, $table_name, $conditions);

        $sql .= $this->buildPagination($pagination);

        return $sql . ';';
    }

    /**
     * @param array{
     *     page?: int,
     *     per_page?: int
     * } $pagination
     *
     * @return string
     */
    private function buildPagination(array $pagination = []): string
    {
        if (!isset($pagination['per_page'])) {
            return '';
        }

        $page = (int)($pagination['page'] ?? 1);

        $offset = ($page - 1) * $pagination['per_page'];

        $result = sprintf(' LIMIT %s OFFSET %s', $pagination['per_page'], $offset);

        return $result;
    }

    /**
     * @return string
     */
    abstract public function buildFetchChildrenMeta(): string;

}