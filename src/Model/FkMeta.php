<?php
declare(strict_types=1);


namespace Richbuilds\Orm\Model;

/**
 *
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
    )
    {
    }
}