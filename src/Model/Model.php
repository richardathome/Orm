<?php
declare(strict_types=1);


namespace Richbuilds\Orm\Model;

use Richbuilds\Orm\Driver\Driver;
use Richbuilds\Orm\OrmException;

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
     * @param string|array<string> $column_name
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
     * @return mixed
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
}