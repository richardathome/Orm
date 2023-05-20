<?php
declare(strict_types=1);


namespace Richbuilds\Orm;

use PDO;
use Richbuilds\Orm\Driver\Driver;
use Richbuilds\Orm\Driver\MySqlDriver\MySqlDriver;
use Richbuilds\Orm\Driver\SqliteDriver\SqliteDriver;
use Richbuilds\Orm\Model\Model;
use Richbuilds\Orm\Query\Query;

/**
 * Main application entry point
 */
class Orm
{

    public readonly Driver $Driver;

    /**
     * @param PDO $pdo
     *
     * @throws OrmException
     */
    public function __construct(
        public readonly PDO $pdo
    )
    {
        $driver_name = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        $this->Driver = match ($driver_name) {
            'mysql' => new MySqlDriver($pdo),
            'sqlite' => new SqliteDriver($pdo),
            default => throw new OrmException(sprintf('unhandled driver %s', $driver_name)),
        };
    }

    /**
     * @param string $table_name
     *
     * @return Model
     *
     * @throws OrmException
     */
    public function Model(string $table_name): Model
    {
        return new Model($this->Driver, $table_name);
    }

    /**
     * @param string $table_name
     * @param array<string,mixed> $conditions
     * @param array<string,mixed> $pagination
     *
     * @return Query
     *
     * @throws OrmException
     */
    public function Query(string $table_name, array $conditions = [], array $pagination = []): Query
    {
        return new Query($this->Driver, $table_name, $conditions, $pagination);
    }
}