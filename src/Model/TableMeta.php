<?php
declare(strict_types=1);


namespace Richbuilds\Orm\Model;

use Richbuilds\Orm\OrmException;

/**
 * Holds the meta information about a table
 */
class TableMeta
{
    /**
     * @param string $database_name
     * @param string $table_name
     * @param array<string,ColumnMeta> $ColumnMeta
     */
    public function __construct(
        public readonly string $database_name,
        public readonly string $table_name,
        public readonly array  $ColumnMeta
    )
    {
    }

    /**
     * @param string $column_name
     *
     * @return void
     *
     * @throws OrmException
     */
    public function guardHasColumn(string $column_name): void
    {
        if (!array_key_exists($column_name, $this->ColumnMeta)) {
            throw new OrmException(sprintf('unknown column %s in %s.%s', $column_name, $this->database_name, $this->table_name));
        }
    }

    /**
     * @param array<string> $column_names
     *
     * @return void
     *
     * @throws OrmException
     */
    public function guardHasColumns(array $column_names = []): void
    {
        foreach($column_names as $column_name) {
            $this->guardHasColumn($column_name);
        }
    }
}