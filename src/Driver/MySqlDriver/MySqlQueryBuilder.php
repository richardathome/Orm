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
     * @inheritDoc
     */
    public function buildFetchDatabaseName(): string
    {
        return 'SELECT DATABASE();';
    }


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
    COLUMNS.COLUMN_KEY AS column_key,
    KEY_COLUMN_USAGE.REFERENCED_TABLE_SCHEMA AS referenced_database,
    KEY_COLUMN_USAGE.REFERENCED_TABLE_NAME AS referenced_table,
    KEY_COLUMN_USAGE.REFERENCED_COLUMN_NAME AS referenced_column,
    REFERRING_TABLES.TABLE_SCHEMA AS referring_database,
    REFERRING_TABLES.TABLE_NAME AS referring_table,
    REFERRING_TABLES.COLUMN_NAME AS referring_column
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
LEFT JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE AS REFERRING_TABLES ON
    COLUMNS.TABLE_SCHEMA = REFERRING_TABLES.REFERENCED_TABLE_SCHEMA
    AND COLUMNS.TABLE_NAME = REFERRING_TABLES.REFERENCED_TABLE_NAME
    AND COLUMNS.COLUMN_NAME = REFERRING_TABLES.REFERENCED_COLUMN_NAME
WHERE
    COLUMNS.TABLE_SCHEMA = :database_name
    AND COLUMNS.TABLE_NAME = :table_name
ORDER BY COLUMNS.ORDINAL_POSITION;
SQL;
    }


    /**
     * @inheritDoc
     */
    public function buildFetchChildrenMeta(): string
    {
        return <<<SQL
SELECT
    CONSTRAINT_NAME,
    TABLE_NAME,
    COLUMN_NAME
FROM
    INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE
    TABLE_SCHEMA = :database_name
    AND REFERENCED_TABLE_NAME = :table_name
SQL;
    }
}