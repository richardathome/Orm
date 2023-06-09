<?php
declare(strict_types=1);

namespace Richbuilds\Orm\Model;

use Richbuilds\Orm\Driver\Driver;
use Richbuilds\Orm\OrmException;

/**
 * Represents a Model's column values keyed by column name
 */
class Values
{

    /**
     * @var array<string,mixed>
     */
    private array $values = [];

    /**
     * @param Driver $driver
     *
     * @param TableMeta $tableMeta
     */
    public function __construct(
        private readonly Driver    $driver,
        private readonly TableMeta $tableMeta
    ) {
    }


    /**
     * Returns the columns current value or null if not set
     *
     * @param string|array<string> $column_name
     *
     * @return mixed|null
     *
     * @throws OrmException
     */
    public function get(array|string $column_name): mixed
    {
        if (is_array($column_name)) {
            // get many

            if (empty($column_name)) {
                $column_name = array_keys($this->tableMeta->columnMeta);
            }

            $values = [];

            foreach ($column_name as $k) {
                $values[$k] = $this->get($k);
            }

            return $values;
        }

        // get one
        $this->tableMeta->guardHasColumn($column_name);

        return $this->values[$column_name] ?? null;
    }


    /**
     * Checks and sets $column_name to $value
     * or if $column_name is an array, sets many values
     *
     * @param array<string,mixed>|string $column_name
     * @param mixed $value
     *
     * @return self
     *
     * @throws OrmException
     */
    public function set(array|string $column_name, mixed $value = null): self
    {
        if (is_array($column_name)) {
            return $this->setMany($column_name);
        }

        if (isset($this->tableMeta->childMeta[$column_name])) {
            return $this->setChildren($column_name, $value);
        }

        if (isset($this->tableMeta->ParentMeta[$column_name])) {
            if (is_array($value)) {
                return $this->setParentArray($column_name, $value);
            }

            if ($value instanceof Model) {
                return $this->setParentModel($column_name, $value);
            }

            return $this->setParentReference($column_name, $value);
        }

        // set a simple value
        $this->tableMeta->guardHasColumn($column_name);

        $this->values[$column_name] = $this->tableMeta->columnMeta[$column_name]->toPhp($value);

        return $this;
    }


    /**
     * Checks and sets many column values at once
     *
     * @param array<string,mixed> $values
     *
     * @return self
     *
     * @throws OrmException
     */
    private function setMany(array $values): self
    {
        $original_value = $this->values;

        try {
            foreach ($values as $k => $v) {
                $this->set($k, $v);
            }

            return $this;
        } catch (OrmException $e) {
            $this->values = $original_value;
            throw ($e);
        }
    }


    /**
     * Checks and sets children values
     *
     * @param string $children_table_name
     * @param mixed $children
     *
     * @return self
     *
     * @throws OrmException
     */
    private function setChildren(string $children_table_name, mixed $children): self
    {
        if (!is_array($children)) {
            throw new OrmException(sprintf(
                '%s.%s.%s: expected array got %s',
                $this->tableMeta->database_name,
                $this->tableMeta->table_name,
                $children_table_name,
                get_debug_type($children)
            ));
        }

        $this->tableMeta->guardHasChild($children_table_name);

        foreach ($children as $key => $child_values) {
            if ($child_values instanceof Model) {
                $model = $child_values;
            } else {
                $model = new Model($this->driver, $children_table_name);
                $model->set($child_values);
            }

            if ($children_table_name !== $model->tableMeta->table_name) {
                throw new OrmException(sprintf(
                    '%s.%s.%s: expected %s got %s',
                    $this->tableMeta->database_name,
                    $this->tableMeta->table_name,
                    $children_table_name,
                    $children_table_name,
                    $model->tableMeta->table_name
                ));
            }

            $this->values[$children_table_name][(string)$key] = $child_values;
        }

        return $this;
    }


    /**
     * Checks and sets a foreign key column from an array source
     *
     * @param string $parent_column_name
     * @param array<string,mixed> $parent_values
     *
     * @return self
     *
     * @throws OrmException
     */
    private function setParentArray(string $parent_column_name, array $parent_values): self
    {
        $this->tableMeta->guardIsForeignKey($parent_column_name);

        // test $parent_values valid for the parent
        $parent_meta = $this->tableMeta->ParentMeta[$parent_column_name];
        $parent_model = new Model($this->driver, $parent_meta->referenced_table_name);
        $parent_model->set($parent_values);

        // values are valid
        $this->values[$parent_column_name] = $parent_model;

        return $this;
    }

    /**
     * Checks and sets a foreign key column from a model source
     *
     * @param string $parent_column_name
     * @param Model $parent_model
     *
     * @return self
     *
     * @throws OrmException
     */
    private function setParentModel(string $parent_column_name, Model $parent_model): self
    {
        $this->tableMeta->guardIsForeignKey($parent_column_name);

        $referenced_table_name = $this->tableMeta->ParentMeta[$parent_column_name]->referenced_table_name;

        if ($referenced_table_name !== $parent_model->tableMeta->table_name) {
            throw new OrmException(sprintf(
                '%s.%s.%s: expected %s, got %s',
                $this->tableMeta->database_name,
                $this->tableMeta->table_name,
                $parent_column_name,
                $referenced_table_name,
                $parent_model->tableMeta->table_name
            ));
        }

        $this->values[$parent_column_name] = $parent_model;

        return $this;
    }

    /**
     * Checks and sets a foreign key column from reference
     *
     * @param string $column_name
     * @param mixed $value
     *
     * @return self
     *
     * @throws OrmException
     */
    private function setParentReference(string $column_name, mixed $value): self
    {
        $this->tableMeta->guardIsForeignKey($column_name);

        $value = $this->tableMeta->columnMeta[$column_name]->toPhp($value);

        if ($value !== null) {
            $parent_fk = $this->tableMeta->ParentMeta[$column_name];

            // ensure foreign key is valid by trying to load the foreign key model
            // TODO: Will fail for composite key
            try {
                (new Model($this->driver, $parent_fk->referenced_table_name))->fetchByPk($value);
            } catch (OrmException $e) {
                throw new OrmException(sprintf(
                    '%s.%s.%s: %s',
                    $this->tableMeta->database_name,
                    $this->tableMeta->table_name,
                    $column_name,
                    $e->getMessage()
                ));
            }
        }

        $this->values[$column_name] = $value;

        return $this;
    }


    /**
     * Returns a filtered array of values without any children columns
     *
     * @return array<string,mixed>
     */
    public function getColumnValues(): array
    {
        $values = $this->values;

        foreach (array_keys($this->tableMeta->childMeta) as $child_table_name) {
            if (isset($values[$child_table_name])) {
                unset($values[$child_table_name]);
            }
        }

        return $values;
    }


    /**
     * Checks if $key exists
     *
     * @param string $column_name
     *
     * @return bool
     */
    public function hasColumn(string $column_name): bool
    {
        return isset($this->values[$column_name]);
    }


    /**
     * Removes $column_name from the list of values
     *
     * @param string $column_name
     *
     * @return $this
     */
    public function remove(string $column_name): self
    {
        unset($this->values[$column_name]);

        return $this;
    }
}
