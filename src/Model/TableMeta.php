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
     * @param array<string> $pk_columns
     * @param array<string,FkMeta> $ParentMeta
     * @param array<string,FkMeta> $ChildrenMeta
     */
    public function __construct(
        public readonly string $database_name,
        public readonly string $table_name,
        public readonly array  $ColumnMeta,
        public array $pk_columns,
        public array $ParentMeta,
        public array $ChildrenMeta
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

    /**
     * @return void
     * @throws OrmException
     */
    public function guardHasPrimaryKey(): void
    {
        if (empty($this->pk_columns)) {
            throw new OrmException(sprintf('%s.%s has no primary key', $this->database_name, $this->table_name));
        }
    }

    /**
     * @param string $child_table_name
     * @return void
     * @throws OrmException
     */
    public function guardHasChild(string $child_table_name): void
    {
        if (!isset($this->ChildrenMeta[$child_table_name])) {
            throw new OrmException(sprintf('%s is not a child of %s.%s', $child_table_name, $this->database_name,$this->table_name));
        }
    }

    /**
     * @param string $column_name
     * @return void
     * @throws OrmException
     */
    public function guardHasParent(string $column_name): void
    {
        if (!isset($this->ParentMeta[$column_name])) {
            throw new OrmException(sprintf('%s.%s.%s is not a foreign key',$this->database_name,$this->table_name, $column_name));
        }
    }
}