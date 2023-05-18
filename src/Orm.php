<?php
declare(strict_types=1);


namespace Richbuilds\Orm;

use PDO;
use Richbuilds\Orm\Driver\Driver;
use Richbuilds\Orm\Driver\MySqlDriver\MySqlDriver;
use Richbuilds\Orm\Model\Model;

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
}