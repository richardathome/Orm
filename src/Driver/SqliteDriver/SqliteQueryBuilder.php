<?php
declare(strict_types=1);


namespace Richbuilds\Orm\Driver\SqliteDriver;

use Richbuilds\Orm\Driver\QueryBuilder;

/**
 *
 */
class SqliteQueryBuilder extends QueryBuilder
{

    /**
     * @inheritDoc
     */
    public function buildFetchDatabaseName(): string
    {
        return '';
    }

    /**
     * @inheritDoc
     */
    public function buildFetchTableMeta(): string
    {
        return <<<SQL
SELECT
    NULL AS database_name,
    :table_name AS table_name,
    COLUMNS.name AS column_name,
    COLUMNS.type AS data_type,
    COLUMNS."notnull" AS allow_null,
    NULL AS "precision",
    NULL AS scale,
    NULL AS max_character_length,
    NULL AS column_key,
    fk."table" AS referenced_table,
    fk."to" AS referenced_column
FROM
    sqlite_master
JOIN
    pragma_table_info(:table_name) AS COLUMNS
    ON sqlite_master.type = 'table' AND COLUMNS.name NOT LIKE 'sqlite_%'
LEFT JOIN
    pragma_foreign_key_list(:table_name) AS fk
    ON COLUMNS.cid = fk."from"
WHERE
    sqlite_master.type = 'table'
    AND sqlite_master.name = :table_name
ORDER BY
    COLUMNS.cid;



SQL;

    }


}