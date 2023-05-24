<?php
declare(strict_types=1);


namespace Richbuilds\Orm\Driver;

use Richbuilds\Orm\Model\TableMeta;

/**
 * builds sql92 compliant queries
 */
abstract class QueryBuilder
{

    /**
     * @return string
     */
    abstract public function buildFetchDatabaseName(): string;

    /**
     * @return string
     */
    abstract public function buildFetchTableMeta(): string;


    /**
     * Returns the sql query to fetch the first matching row
     *
     * @param TableMeta $table_meta
     * @param array<string,mixed> $conditions
     *
     * @return string
     */
    public function buildFetchFirstBy(TableMeta $table_meta, array $conditions = []): string
    {
        $sql = sprintf('SELECT * FROM `%s`.`%s`', $table_meta->database_name, $table_meta->table_name);

        $sql .= $this->buildWhere($table_meta, $conditions);

        $sql .= ' LIMIT 1';

        return $sql . ';';
    }


    /**
     * Builds a where clause from $conditions
     *
     * @param TableMeta $table_meta
     * @param array<string,mixed> $conditions
     *
     * @return string
     */
    protected function buildWhere(TableMeta $table_meta, array $conditions = []): string
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
                $value = is_array($value) ? $value : [$value];

                foreach ($value as $index => $item) {
                    $placeholder = ':' . $column_name . '_value_' . $index;
                    $placeholders[] = $placeholder;
                }
                $where .= sprintf(
                    '`%s`.`%s`.`%s` IN (%s) AND ',
                    $table_meta->database_name,
                    $table_meta->table_name,
                    $column_name,
                    implode(', ', $placeholders)
                );
            } else {
                $where .= sprintf(
                    '`%s`.`%s`.`%s` %s :%s AND ',
                    $table_meta->database_name,
                    $table_meta->table_name,
                    $column_name,
                    $comparator,
                    $column_name
                );
            }
        }

        $where = substr($where, 0, -5);

        return $where;
    }


    /**
     * Returns the sql to fetch all rows matching $conditions
     * @param TableMeta $table_meta
     * @param array<string,mixed> $conditions
     * @param array<string,mixed> $pagination
     *
     * @return string
     */
    public function buildFetchAll(TableMeta $table_meta, array $conditions = [], array $pagination = []): string
    {
        $sql = sprintf('SELECT * FROM `%s`.`%s`', $table_meta->database_name, $table_meta->table_name);

        $sql .= $this->buildWhere($table_meta, $conditions);

        $sql .= $this->buildPagination($pagination);

        return $sql . ';';
    }


    /**
     * Builds the LIMIT and OFFSET clause
     *
     * @param array{
     *     page?: int,
     *     per_page?: int
     * } $pagination
     *
     * @return string
     */
    protected function buildPagination(array $pagination = []): string
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
     * Builds the SQL statement to insert $values
     *
     * @param TableMeta $table_meta
     * @param array<string,mixed> $values
     * @param array<string,mixed> $conditions
     *
     * @return string
     */
    public function buildInsert(TableMeta $table_meta, array $values, array $conditions): string
    {
        /** @noinspection Annotator */
        $sql = sprintf('INSERT INTO `%s`.`%s` (`', $table_meta->database_name, $table_meta->table_name)
            . join('`, `', array_keys($values))
            . '`) VALUES ('
            . ':' . join(', :', array_keys($values))
            . ')'
            . $this->buildWhere($table_meta, $conditions);

        return $sql . ';';
    }


    /**
     * Builds the SQL statement to update $table_name with $values
     *
     * @param TableMeta $table_meta
     * @param array<string,mixed> $values
     * @param array<string,mixed> $conditions
     *
     * @return string
     */
    public function buildUpdate(TableMeta $table_meta, array $values, array $conditions): string
    {
        /** @noinspection Annotator */
        $sql = sprintf('UPDATE `%s`.`%s` SET ', $table_meta->database_name, $table_meta->table_name);

        foreach ($values as $column_name => $value) {
            $sql .= '`' . $column_name . '` = :' . $column_name . ',';
        }

        $sql = substr($sql, 0, -1);

        $sql .= $this->buildWhere($table_meta, $conditions);

        return $sql . ';';
    }

    /**
     * @param TableMeta $table_meta
     * @param array<string,mixed> $conditions
     *
     * @return string
     */
    public function buildDelete(TableMeta $table_meta, array $conditions): string
    {
        $sql = sprintf('DELETE FROM `%s`.`%s`', $table_meta->database_name, $table_meta->table_name);

        $sql .= $this->buildWhere($table_meta, $conditions);

        return $sql;
    }
}
