<?php
declare(strict_types=1);


namespace Richbuilds\Orm\Model;

use Richbuilds\Orm\Driver\Driver;
use Richbuilds\Orm\OrmException;
use Richbuilds\Orm\Query\Query;
use RuntimeException;
use Throwable;

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
        private readonly Driver $Driver,
        string                 $table_name
    )
    {
        $this->TableMeta = $this->Driver->fetchTableMeta($table_name);
        $this->Values = new Values($this->Driver, $this->TableMeta);
    }


    /**
     * Sets $column_name to $value
     *
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


    /**
     * Returns the value of a column
     *
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
     * Fetches a new Model based on $conditions
     *
     * @param array<string,mixed> $conditions
     *
     * @return Model
     *
     * @throws OrmException
     */
    public function fetchBy(array $conditions = []): Model
    {
        $this->TableMeta->guardHasColumns(array_keys($conditions));

        $values = $this->Driver->fetchFirstBy($this->TableMeta, $conditions);

        if ($values === false) {
            throw new OrmException(sprintf('%s.%s record not found', $this->TableMeta->database_name, $this->TableMeta->table_name));
        }

        $model = new Model($this->Driver, $this->TableMeta->table_name);
        $model->set($values);

        return $model;
    }


    /**
     * Returns the primary key column values(s)
     *
     * To get the primary key column names: Model->TableMeta->pk_columns[]
     *
     * @return array<string,mixed>
     *
     * @throws OrmException
     */
    public function getPk(): array
    {
        $this->TableMeta->guardHasPrimaryKey();

        return $this->Values->get($this->TableMeta->pk_columns);
    }


    /**
     * Fetches a new Model by its primary key
     *
     * @param int|string|array<string,mixed> $value
     *
     * @return Model
     *
     * @throws OrmException
     */
    public function fetchByPk(int|string|array $value): Model
    {
        $this->TableMeta->guardHasPrimaryKey();

        if (!is_array($value)) {
            // fetch by simple pk

            if (count($this->TableMeta->pk_columns) > 1) {
                throw new OrmException(sprintf('%s.%s: array expected', $this->TableMeta->database_name, $this->TableMeta->table_name));
            }

            return $this->fetchBy([$this->TableMeta->pk_columns[0] => $value]);
        }

        // fetch by composite pk
        foreach ($this->TableMeta->pk_columns as $pk_column_name) {

            if (!isset($value[$pk_column_name])) {
                throw new OrmException(sprintf('missing pk column %s ', $pk_column_name));
            }
        }

        return $this->fetchBy($value);
    }


    /**
     * Lazily fetches Child $child_table_name Models associated with this Model
     *
     * @param string $child_table_name
     * @param array<string,mixed> $conditions
     *
     * @return Query
     *
     * @throws OrmException
     */
    public function fetchChildren(string $child_table_name, array $conditions = []): Query
    {
        $this->TableMeta->guardHasChild($child_table_name);
        $this->TableMeta->guardHasPrimaryKey();


        if ($this->isPkNull()) {
            throw new OrmException('primary key not set');
        }

        $pk_value = $this->getPk();

        if (count($pk_value) !== 1) {
            throw new OrmException('multi-column foreign keys not supported');
        }

        $child_column_name = $this->TableMeta->ChildrenMeta[$child_table_name]->referenced_column_name;

        $conditions[$child_column_name] = $pk_value[$this->TableMeta->pk_columns[0]];

        $query = new Query($this->Driver, $child_table_name, $conditions);

        return $query;
    }


    /**
     * Fetches the parent model pointed to by $column_name
     *
     * @param string $fk_column_name
     *
     * @return Model
     *
     * @throws OrmException
     */
    public function fetchParent(string $fk_column_name): Model
    {
        $this->TableMeta->guardIsForeignKey($fk_column_name);

        $parent_meta = $this->TableMeta->ParentMeta[$fk_column_name];

        if ($this->Values->get($fk_column_name) === null) {
            throw new OrmException(sprintf('%s.%s.%s is null', $this->TableMeta->database_name, $this->TableMeta->table_name, $fk_column_name));
        }

        $child = (new Model($this->Driver, $parent_meta->referenced_table_name))
            ->fetchByPk($this->Values->get($fk_column_name));

        return $child;
    }


    /**
     * Save the Model, it's parents and it's children, wrapped in a transaction
     *
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
     * Saves a model recursively
     *
     * @return void
     *
     * @throws OrmException
     */
    private function _save(): void
    {
        $this->saveParents();

        $values = $this->Values->getColumnValues();

        if ($this->isPkNull()) {
            $insert_id = $this->Driver->insert($this->TableMeta, $values);

            $this->Values->set($this->TableMeta->pk_columns[0], $insert_id);
        } else {

            /**
             * @var array<string,mixed> $conditions
             */
            $conditions = [];

            foreach ($this->TableMeta->pk_columns as $column_name) {
                $conditions[$column_name] = $this->Values->get($column_name);
            }

            $affected = $this->Driver->update($this->TableMeta, $values, $conditions);

            if ($affected !== 1) {
                throw new RuntimeException('impossible?');
            }
        }

        $this->saveChildren();
    }


    /**
     * Saves any foreign key models
     *
     * @return void
     *
     * @throws OrmException
     */
    private function saveParents(): void
    {
        foreach ($this->TableMeta->ParentMeta as $column_name => $parent_meta) {

            if (!$this->Values->hasColumn($column_name)) {
                continue;
            }

            $parent = $this->Values->get($column_name);

            if ($parent instanceof Model) {
                $parent->_save();

                if ($parent->isPkNull()) {
                    throw new RuntimeException('impossible?');
                }

                $parent_pk_value = $parent->getPk();

                //set the fk column in this model to the primary key of the parent model
                $this->Values->set($column_name, $parent_pk_value[$parent->TableMeta->pk_columns[0]]);
            }
        }
    }


    /**
     * Saves any children models
     *
     * @return void
     *
     * @throws OrmException
     */
    private function saveChildren(): void
    {
        foreach ($this->TableMeta->ChildrenMeta as $child_table_name => $child_meta) {

            if ($this->Values->hasColumn($child_table_name)) {

                $children = $this->Values->get($child_table_name);

                foreach ($children as $child_data) {

                    if ($child_data instanceof Model) {
                        $child_model = $child_data;
                    } else {
                        $child_model = new Model($this->Driver, $child_table_name);
                        $child_model->set($child_data);
                    }

                    // TODO: will fail for composite keys
                    $pk_value = $this->get($this->TableMeta->pk_columns[0]);
                    $child_model->set($child_meta->referenced_column_name, $pk_value);
                    $child_model->_save();
                }

                $this->Values->remove($child_table_name);
            }
        }
    }


    /**
     * Deletes the current model
     *
     * @return void
     *
     * @throws OrmException
     */
    public function delete(): void
    {
        $this->TableMeta->guardHasPrimaryKey();

        if ($this->isPkNull()) {
            throw new OrmException('primary key not set');
        }

        try {
            $this->Driver->delete($this->TableMeta, $this->getPk());
        } catch (Throwable $e) {
            throw new OrmException($e->getMessage());
        }
    }


    /**
     * @return bool
     *
     * @throws OrmException
     */
    public function isPkNull(): bool
    {
        foreach ($this->TableMeta->pk_columns as $pk_column_name) {

            if ($this->get($pk_column_name) !== null) {
                return false;
            }
        }

        return true;
    }

}