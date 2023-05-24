<?php
declare(strict_types=1);


namespace Richbuilds\Orm\Model;

/**
 * Stores information about the foreign keys in a table
 */
class FkMeta
{

    /**
     * @param string $referenced_database_name
     * @param string $referenced_table_name
     * @param string $referenced_column_name
     */
    public function __construct(
        public readonly string $referenced_database_name,
        public readonly string $referenced_table_name,
        public readonly string $referenced_column_name
    ) {
    }
}
