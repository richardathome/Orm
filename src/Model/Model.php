<?php
declare(strict_types=1);


namespace Richbuilds\Orm\Model;

use Richbuilds\Orm\Driver\Driver;
use Richbuilds\Orm\OrmException;
use Richbuilds\Orm\Query\Query;

/**
 * Represents a row in a database
 */
class Model
{
    public readonly TableMeta $TableMeta;

    /**
     * @var array<string,mixed>
     */
    private array $values = [];

    /**
     * @param Driver $Driver
     * @param string $table_name
     *
     * @throws OrmException
     */
    public function __construct(
        public readonly Driver $Driver,
        string                 $table_name
    )
    {
        $this->TableMeta = $this->Driver->fetchTableMeta($table_name);
    }


    /**
     * @param string|array<string,mixed> $column_name
     * @param mixed|null $value
     *
     * @return $this
     *
     * @throws OrmException
     */
    public function set(string|array $column_name, mixed $value = null): self
    {
        if (is_array($column_name)) {
            $original_value = $this->values;

            try {

                foreach ($column_name as $k => $v) {
                    $this->set($k, $v);
                }

                return $this;

            } catch (OrmException $e) {
                $this->values = $original_value;
                throw ($e);
            }
        }

        $this->TableMeta->guardHasColumn($column_name);

        $this->values[$column_name] = $this->TableMeta->ColumnMeta[$column_name]->toPhp($value);

        return $this;
    }

    /**
     * @param string|array<string> $column_name
     *
     * @return mixed|null
     *
     * @throws OrmException
     */
    public function get(string|array $column_name = []): mixed
    {
        if (is_array($column_name)) {

            if (empty($column_name)) {
                $column_name = array_keys($this->TableMeta->ColumnMeta);
            }

            $values = [];

            foreach ($column_name as $k) {
                $values[$k] = $this->get($k);
            }

            return $values;
        }

        $this->TableMeta->guardHasColumn($column_name);

        return $this->values[$column_name] ?? null;
    }

    /**
     * @param array<string,mixed> $conditions
     *
     * @return Model
     *
     * @throws OrmException
     */
    public function fetchBy(array $conditions = []): Model
    {
        $this->TableMeta->guardHasColumns(array_keys($conditions));

        $values = $this->Driver->fetchFirstBy($this->TableMeta->database_name, $this->TableMeta->table_name, $conditions);

        if ($values === false) {
            throw new OrmException(sprintf('%s.%s not found', $this->TableMeta->database_name, $this->TableMeta->table_name));
        }

        $model = new Model($this->Driver, $this->TableMeta->table_name);
        $model->set($values);

        return $model;
    }

    /**
     * @return int|string|array<string,mixed>|null
     *
     * @throws OrmException
     */
    public function getPk(): int|string|array|null
    {
        $this->TableMeta->guardHasPrimaryKey();

        $pk_column_names = $this->TableMeta->pk_columns;

        if (count($pk_column_names) === 1) {
            return $this->get($pk_column_names[0]);
        }

        return $this->get($pk_column_names);
    }

    /**
     * @param int|string|array<string,mixed> $value
     *
     * @return Model
     *
     * @throws OrmException
     */
    public function fetchByPk(int|string|array $value): Model
    {
        $this->TableMeta->guardHasPrimaryKey();

        if (count($this->TableMeta->pk_columns) === 1) {

            if (is_array($value)) {
                throw new OrmException(sprintf('%s.%s: scalar expected', $this->TableMeta->database_name, $this->TableMeta->table_name));
            }

            return $this->fetchBy([$this->TableMeta->pk_columns[0] => $value]);
        }

        if (!is_array($value)) {
            throw new OrmException(sprintf('%s.%s: array expected', $this->TableMeta->database_name, $this->TableMeta->table_name));
        }

        return $this->fetchBy($value);
    }

    /**
     * @param string $child_table_name
     *
     * @return Query
     *
     * @throws OrmException
     */
    public function fetchChildren(string $child_table_name): Query
    {
        $this->TableMeta->guardHasChild($child_table_name);

        $child_column_name = $this->TableMeta->ChildrenMeta[$child_table_name]->referenced_column_name;

        $conditions = [
            $child_column_name => $this->getPk()
        ];

        $query = new Query($this->Driver, $child_table_name, $conditions);

        return $query;
    }

    /**
     * @param string $column_name
     *
     * @return Model
     *
     * @throws OrmException
     */
    public function fetchParent(string $column_name): Model
    {
        $this->TableMeta->guardHasParent($column_name);

        $parent_meta = $this->TableMeta->ParentMeta[$column_name];

        $child = (new Model($this->Driver, $parent_meta->referenced_table_name))
            ->fetchByPk($this->get($column_name));

        return $child;
    }

}