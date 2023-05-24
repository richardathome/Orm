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
    public readonly TableMeta $tableMeta;

    /**
     * @var Values $values
     */
    private Values $values;

    /**
     * @param Driver $driver
     * @param string $table_name
     *
     * @throws OrmException
     */
    public function __construct(
        private readonly Driver $driver,
        string                  $table_name
    ) {
        $this->tableMeta = $this->driver->fetchTableMeta($table_name);
        $this->values = new Values($this->driver, $this->tableMeta);
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
        $this->values->set($column_name, $value);

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
        return $this->values->get($column_name);
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
        $this->tableMeta->guardHasColumns(array_keys($conditions));

        $values = $this->driver->fetchFirstBy($this->tableMeta, $conditions);

        if ($values === false) {
            throw new OrmException(sprintf(
                '%s.%s record not found',
                $this->tableMeta->database_name,
                $this->tableMeta->table_name
            ));
        }

        $model = new Model($this->driver, $this->tableMeta->table_name);
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
        $this->tableMeta->guardHasPrimaryKey();

        return $this->values->get($this->tableMeta->pk_columns);
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
        $this->tableMeta->guardHasPrimaryKey();

        if (!is_array($value)) {
            // fetch by simple pk

            if (count($this->tableMeta->pk_columns) > 1) {
                throw new OrmException(sprintf(
                    '%s.%s: array expected',
                    $this->tableMeta->database_name,
                    $this->tableMeta->table_name
                ));
            }

            return $this->fetchBy([$this->tableMeta->pk_columns[0] => $value]);
        }

        // fetch by composite pk
        foreach ($this->tableMeta->pk_columns as $pk_column_name) {
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
        $this->tableMeta->guardHasChild($child_table_name);
        $this->tableMeta->guardHasPrimaryKey();


        if ($this->isPkNull()) {
            throw new OrmException('primary key not set');
        }

        $pk_value = $this->getPk();

        if (count($pk_value) !== 1) {
            throw new OrmException('multi-column foreign keys not supported');
        }

        $child_column_name = $this->tableMeta->ChildrenMeta[$child_table_name]->referenced_column_name;

        $conditions[$child_column_name] = $pk_value[$this->tableMeta->pk_columns[0]];

        $query = new Query($this->driver, $child_table_name, $conditions);

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
        $this->tableMeta->guardIsForeignKey($fk_column_name);

        $parent_meta = $this->tableMeta->ParentMeta[$fk_column_name];

        if ($this->values->get($fk_column_name) === null) {
            throw new OrmException(sprintf(
                '%s.%s.%s is null',
                $this->tableMeta->database_name,
                $this->tableMeta->table_name,
                $fk_column_name
            ));
        }

        $child = (new Model($this->driver, $parent_meta->referenced_table_name))
            ->fetchByPk($this->values->get($fk_column_name));

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
        $original_values = $this->values;
        $this->driver->beginTransaction();

        try {
            $this->saveModel();

            $this->driver->commitTransaction();

            return $this;
        } catch (OrmException $e) {
            $this->values = $original_values;
            $this->driver->rollbackTransaction();

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
    private function saveModel(): void
    {
        $this->saveParents();

        $values = $this->values->getColumnValues();

        foreach ($values as $column_name => $value) {
            $values[$column_name] = $this->tableMeta->ColumnMeta[$column_name]->toSql($value);
        }

        if ($this->isPkNull()) {
            $insert_id = $this->driver->insert($this->tableMeta, $values);

            $this->values->set($this->tableMeta->pk_columns[0], $insert_id);
        } else {

            /**
             * @var array<string,mixed> $conditions
             */
            $conditions = [];

            foreach ($this->tableMeta->pk_columns as $column_name) {
                $conditions[$column_name] = $this->values->get($column_name);
            }

            $affected = $this->driver->update($this->tableMeta, $values, $conditions);

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
        foreach ($this->tableMeta->ParentMeta as $column_name => $parent_meta) {
            if (!$this->values->hasColumn($column_name)) {
                continue;
            }

            $parent = $this->values->get($column_name);

            if ($parent instanceof Model) {
                $parent->saveModel();

                if ($parent->isPkNull()) {
                    throw new RuntimeException('impossible?');
                }

                $parent_pk_value = $parent->getPk();

                //set the fk column in this model to the primary key of the parent model
                $this->values->set($column_name, $parent_pk_value[$parent->tableMeta->pk_columns[0]]);
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
        foreach ($this->tableMeta->ChildrenMeta as $child_table_name => $child_meta) {
            if ($this->values->hasColumn($child_table_name)) {
                $children = $this->values->get($child_table_name);

                foreach ($children as $child_data) {
                    if ($child_data instanceof Model) {
                        $child_model = $child_data;
                    } else {
                        $child_model = new Model($this->driver, $child_table_name);
                        $child_model->set($child_data);
                    }

                    // TODO: will fail for composite keys
                    $pk_value = $this->get($this->tableMeta->pk_columns[0]);
                    $child_model->set($child_meta->referenced_column_name, $pk_value);
                    $child_model->saveModel();
                }

                $this->values->remove($child_table_name);
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
        $this->tableMeta->guardHasPrimaryKey();

        if ($this->isPkNull()) {
            throw new OrmException('primary key not set');
        }

        try {
            $this->driver->delete($this->tableMeta, $this->getPk());
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
        foreach ($this->tableMeta->pk_columns as $pk_column_name) {
            if ($this->get($pk_column_name) !== null) {
                return false;
            }
        }

        return true;
    }
}
