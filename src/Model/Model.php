<?php
declare(strict_types=1);


namespace Richbuilds\Orm\Model;

use Richbuilds\Orm\Driver\Driver;
use Richbuilds\Orm\OrmException;
use Richbuilds\Orm\Query\Query;
use RuntimeException;

/**
 * Represents a row in a database
 */
class Model
{
    public readonly TableMeta $TableMeta;

    /**
     * @var Values $Values
     */
    private Values $Values;

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
        $this->Values = new Values($this->Driver, $this->TableMeta);
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
        return $this->Values->get($column_name);
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
            throw new OrmException(sprintf('%s.%s record not found', $this->TableMeta->database_name, $this->TableMeta->table_name));
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
            return $this->Values->get($pk_column_names[0]);
        }

        return $this->Values->get($pk_column_names);
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
     * @param array<string,mixed> $conditions
     * @return Query
     *
     * @throws OrmException
     */
    public function fetchChildren(string $child_table_name, array $conditions= []): Query
    {
        $this->TableMeta->guardHasChild($child_table_name);

        $child_column_name = $this->TableMeta->ChildrenMeta[$child_table_name]->referenced_column_name;

        $conditions[$child_column_name] = $this->getPk();

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

        if ($this->Values->get($column_name) === null) {
            throw new OrmException(sprintf('%s.%s.%s is null', $this->TableMeta->database_name,$this->TableMeta->table_name,$column_name));
        }

        $child = (new Model($this->Driver, $parent_meta->referenced_table_name))
            ->fetchByPk($this->Values->get($column_name));

        return $child;
    }

    /**
     * @return self
     *
     * @throws OrmException
     */
    public function save(): self
    {
        $original_values = $this->Values;
        $this->Driver->beginTransaction();

        try {
            $this->_save();

            $this->Driver->commitTransaction();

            return $this;
        } catch (OrmException $e) {
            $this->Values = $original_values;
            $this->Driver->rollbackTransaction();

            throw $e;
        }
    }

    /**
     * @return void
     *
     * @throws OrmException
     */
    private function _save(): void
    {
        $this->saveParents();

        $values = $this->Values->getColumnValues();

        if ($this->getPk() === null) {
            $insert_id = $this->Driver->insert(
                $this->TableMeta->database_name,
                $this->TableMeta->table_name,
                $values
            );

            $this->Values->set($this->TableMeta->pk_columns[0], $insert_id);
        } else {

            /**
             * @var array<string,mixed> $conditions
             */
            $conditions = [];

            foreach ($this->TableMeta->pk_columns as $column_name) {
                $conditions[$column_name] = $this->Values->get($column_name);
            }

            $affected = $this->Driver->update(
                $this->TableMeta->database_name,
                $this->TableMeta->table_name,
                $values,
                $conditions
            );

            if ($affected !== 1) {
                throw new RuntimeException('how?');
            }
        }

        $this->saveChildren();
    }


    /**
     * @return void
     *
     * @throws OrmException
     */
    private function saveParents(): void
    {
        foreach ($this->TableMeta->ParentMeta as $column_name => $parent_meta) {

            if (!$this->Values->has($column_name)) {
                continue;
            }

            $parent = $this->Values->get($column_name);

            if ($parent instanceof Model) {
                $parent->_save();

                //set the fk column in this model to the primary key of the parent model
                $this->Values->set($column_name, $parent->getPk());
            }
        }
    }


    /**
     * @return void
     *
     * @throws OrmException
     */
    private function saveChildren(): void
    {
        foreach ($this->TableMeta->ChildrenMeta as $child_table_name => $child_meta) {

            if ($this->Values->has($child_table_name)) {

                $children = $this->Values->get($child_table_name);

                foreach ($children as $child_data) {

                    if ($child_data instanceof Model) {
                        $child_model = $child_data;
                    } else {
                        $child_model = new Model($this->Driver, $child_table_name);
                        $child_model->set($child_data);
                    }

                    // TODO: will fail for composite keys
                    $child_model->set($child_meta->referenced_column_name, $this->getPk());
                    $child_model->_save();
                }

                $this->Values->remove($child_table_name);
            }
        }
    }


    /**
     * @param string|array<string,mixed> $column_name
     * @param mixed|null $value
     *
     * @return self
     *
     * @throws OrmException
     */
    public function set(string|array $column_name, mixed $value = null): self
    {
        $this->Values->set($column_name, $value);

        return $this;
    }

}