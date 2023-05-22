<?php
declare(strict_types=1);


namespace Richbuilds\Orm\Model;

use Richbuilds\Orm\Driver\Driver;
use Richbuilds\Orm\OrmException;

/**
 * Represents a Model's column values
 */
class Values
{

    /**
     * @var array<string,mixed>
     */
    private array $values = [];

    /**
     * @param Driver $Driver
     *
     * @param TableMeta $TableMeta
     */
    public function __construct(
        private readonly Driver    $Driver,
        private readonly TableMeta $TableMeta
    )
    {
    }

    /**
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
                $column_name = array_keys($this->TableMeta->ColumnMeta);
            }

            $values = [];

            foreach ($column_name as $k) {
                $values[$k] = $this->get($k);
            }

            return $values;
        }

        // get one
        $this->TableMeta->guardHasColumn($column_name);

        return $this->values[$column_name] ?? null;
    }

    /**
     * @param array<string,mixed>|string $column_name
     * @param mixed $value
     *
     * @return self
     *
     * @throws OrmException
     */
    public function set(array|string $column_name, mixed $value): self
    {
        if (is_array($column_name)) {
            return $this->setMany($column_name);
        }

        if (isset($this->TableMeta->ChildrenMeta[$column_name])) {
            return $this->setChildren($column_name, $value);
        }

        if (isset($this->TableMeta->ParentMeta[$column_name])) {

            if (is_array($value)) {
                return $this->setParentArray($column_name, $value);
            }

            if ($value instanceof Model) {
                return $this->setParentModel($column_name, $value);
            }

            return $this->setParentValue($column_name, $value);

        }

        // set a simple value
        $this->TableMeta->guardHasColumn($column_name);

        $this->values[$column_name] = $this->TableMeta->ColumnMeta[$column_name]->toPhp($value);

        return $this;
    }


    /**
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
     * @param string $children_table_name
     * @param mixed $children
     * @return self
     * @throws OrmException
     */
    private function setChildren(string $children_table_name, mixed $children): self
    {
        if (!is_array($children)) {
            throw new OrmException(sprintf(
                '%s.%s.%s expected array got %s',
                $this->TableMeta->database_name,
                $this->TableMeta->table_name,
                $children_table_name,
                get_debug_type($children)
            ));
        }

        $this->TableMeta->guardHasChild($children_table_name);

        foreach ($children as $key => $child_values) {

            if ($child_values instanceof Model) {
                $model = $child_values;
            } else {
                $model = new Model($this->Driver, $children_table_name);
                $model->set($child_values);
            }

            if ($children_table_name !== $model->TableMeta->table_name) {
                throw new OrmException(sprintf(
                    '%s.%s expected %s got %s',
                    $this->TableMeta->database_name,
                    $this->TableMeta->table_name,
                    $children_table_name,
                    $model->TableMeta->table_name
                ));
            }

            $this->values[$children_table_name][(string)$key] = $child_values;
        }

        return $this;
    }

    /**
     * @param string $parent_column_name
     * @param array<string,mixed> $parent_values
     *
     * @return self
     *
     * @throws OrmException
     */
    private function setParentArray(string $parent_column_name, array $parent_values): self
    {
        $this->TableMeta->guardHasParent($parent_column_name);

        // test $parent_values valid for the parent
        $parent_meta = $this->TableMeta->ParentMeta[$parent_column_name];
        $parent_model = new Model($this->Driver, $parent_meta->referenced_table_name);
        $parent_model->set($parent_values);

        // values are valid
        $this->values[$parent_column_name] = $parent_model;

        return $this;
    }

    /**
     * @param string $parent_column_name
     * @param Model $parent_model
     *
     * @return self
     *
     * @throws OrmException
     */
    private function setParentModel(string $parent_column_name, Model $parent_model): self
    {
        $this->TableMeta->guardHasParent($parent_column_name);

        if ($this->TableMeta->ParentMeta[$parent_column_name]->referenced_table_name !== $parent_model->TableMeta->table_name) {
            throw new OrmException(sprintf(
                '%s.%s.%s: expected %s, got %s',
                $this->TableMeta->database_name,
                $this->TableMeta->table_name,
                $parent_column_name,
                $this->TableMeta->ParentMeta[$parent_column_name]->referenced_table_name,
                $parent_model->TableMeta->table_name
            ));
        }

        $this->values[$parent_column_name] = $parent_model;

        return $this;
    }

    /**
     * @param string $column_name
     * @param mixed $value
     *
     * @return self
     *
     * @throws OrmException
     */
    private function setParentValue(string $column_name, mixed $value): self
    {
        $this->TableMeta->guardHasParent($column_name);

        $value = $this->TableMeta->ColumnMeta[$column_name]->toPhp($value);

        if ($value !== null) {
            $parent_fk = $this->TableMeta->ParentMeta[$column_name];

            // ensure foreign key is valid by trying to load the foreign key model
            // TODO: Will fail for composite key
            try {
                (new Model($this->Driver, $parent_fk->referenced_table_name))->fetchByPk($value);
            } catch (OrmException $e) {
                throw new OrmException(sprintf('%s.%s.%s: %s', $this->TableMeta->database_name, $this->TableMeta->table_name, $column_name, $e->getMessage()));
            }
        }

        $this->values[$column_name] = $value;

        return $this;
    }

    /**
     * @return array<string,mixed>
     */
    public function getColumnValues(): array
    {

        $values = $this->values;

        // remove any children values as they are saved after this record is created
        foreach (array_keys($this->TableMeta->ChildrenMeta) as $child_table_name) {
            if (isset($values[$child_table_name])) {
                unset($values[$child_table_name]);
            }
        }

        return $values;
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function has(string $key): bool
    {
        return isset($this->values[$key]);
    }

    /**
     * @param string $key
     *
     * @return $this
     */
    public function remove(string $key): self
    {
        unset($this->values[$key]);

        return $this;
    }


}