<?php
declare(strict_types=1);


namespace Richbuilds\Orm\Driver\MySqlDriver;

use Richbuilds\Orm\Driver\QueryBuilder;

/**
 * Responsible for building mysql queries
 */
class MySqlQueryBuilder extends QueryBuilder
{

    /**
     * Returns the query to fetch the metadata for all the columns in $database_name.$table_name
     *
     * @return string
     */
    public function buildFetchTableMeta(): string
    {
        return <<<SQL
SELECT
  COLUMNS.TABLE_SCHEMA AS database_name,
  COLUMNS.TABLE_NAME AS table_name,
  COLUMNS.COLUMN_NAME AS column_name,
  COLUMNS.DATA_TYPE AS data_type,
  COLUMNS.COLUMN_TYPE AS column_type,
  COLUMNS.IS_NULLABLE AS allow_null,
  COLUMNS.NUMERIC_PRECISION AS `precision`,
  COLUMNS.NUMERIC_SCALE AS scale,
  COLUMNS.CHARACTER_MAXIMUM_LENGTH AS max_character_length,
  COLUMNS.COLUMN_KEY as column_key,
  REFERENTIAL_CONSTRAINTS.CONSTRAINT_NAME AS foreign_key_constraint_name,
  REFERENTIAL_CONSTRAINTS.UNIQUE_CONSTRAINT_NAME AS foreign_key_unique_constraint_name,
  KEY_COLUMN_USAGE.REFERENCED_TABLE_SCHEMA AS referenced_database,
  KEY_COLUMN_USAGE.REFERENCED_TABLE_NAME AS referenced_table,
  KEY_COLUMN_USAGE.REFERENCED_COLUMN_NAME AS referenced_column
FROM
  INFORMATION_SCHEMA.COLUMNS
LEFT JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE ON
  COLUMNS.TABLE_SCHEMA = KEY_COLUMN_USAGE.TABLE_SCHEMA
  AND COLUMNS.TABLE_NAME = KEY_COLUMN_USAGE.TABLE_NAME
  AND COLUMNS.COLUMN_NAME = KEY_COLUMN_USAGE.COLUMN_NAME
LEFT JOIN INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS ON
  KEY_COLUMN_USAGE.TABLE_SCHEMA = REFERENTIAL_CONSTRAINTS.CONSTRAINT_SCHEMA
  AND KEY_COLUMN_USAGE.TABLE_NAME = REFERENTIAL_CONSTRAINTS.TABLE_NAME
  AND KEY_COLUMN_USAGE.CONSTRAINT_NAME = REFERENTIAL_CONSTRAINTS.CONSTRAINT_NAME
WHERE
  COLUMNS.TABLE_SCHEMA = :database_name
  AND COLUMNS.TABLE_NAME = :table_name
ORDER BY COLUMNS.ORDINAL_POSITION;
SQL;
    }


    /**
     * @inheritDoc
     */
    public function buildFetchDatabaseName(): string
    {
        return 'SELECT DATABASE();';
    }
}