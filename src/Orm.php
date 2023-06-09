<?php
declare(strict_types=1);

namespace Richbuilds\Orm;

use PDO;
use Richbuilds\Orm\Driver\Driver;
use Richbuilds\Orm\Driver\MySqlDriver\MySqlDriver;
use Richbuilds\Orm\Model\Model;
use Richbuilds\Orm\Query\Query;

/**
 * Main application entry point
 */
class Orm
{

    /**
     * Driver responsible for interacting with the database
     *
     * @var Driver $driver
     */
    public readonly Driver $driver;

    /**
     * @param PDO $pdo
     *
     * @throws OrmException
     */
    public function __construct(
        public readonly PDO $pdo
    ) {
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);

        $driver_name = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        $this->driver = match ($driver_name) {
            'mysql' => new MySqlDriver($this->pdo),
            default => throw new OrmException(sprintf('unhandled driver %s', $driver_name)),
        };
    }


    /**
     * Returns a new, empty Model bound to $table_name
     *
     * @param string $table_name
     *
     * @return Model
     *
     * @throws OrmException
     */
    public function model(string $table_name): Model
    {
        return new Model($this->driver, $table_name);
    }


    /**
     * Returns a Query bound to $table_name ready to be iterated over
     *
     * @param string $table_name
     * @param array<string,mixed> $conditions
     * @param array<string,mixed> $pagination
     *
     * @return Query
     *
     * @throws OrmException
     */
    public function query(string $table_name, array $conditions = [], array $pagination = []): Query
    {
        return new Query($this->driver, $table_name, $conditions, $pagination);
    }
}
