<?php
declare(strict_types=1);

namespace Richbuilds\Orm\Query;

use Countable;
use Iterator;
use PDO;
use PDOStatement;
use ReturnTypeWillChange;
use Richbuilds\Orm\Driver\Driver;
use Richbuilds\Orm\Model\Model;
use Richbuilds\Orm\Model\TableMeta;
use Richbuilds\Orm\OrmException;

/**
 * Represents an iterable collection on models
 *
 * @implements  Iterator<int|string, mixed>
 */
class Query implements Iterator, Countable
{
    public readonly TableMeta $TableMeta;

    /**
     * @var PDOStatement|null The PDO statement to be iterated
     */
    protected null|PDOStatement $statement = null;

    /**
     * @var Model|null The current Model instance during iteration
     */
    protected null|Model $currentModel = null;

    /**
     * @var int The current position in the iteration
     */
    protected int $position = 0;

    /**
     * @param Driver $Driver
     * @param string $table_name
     * @param array<string,mixed> $conditions
     * @param array<string,mixed> $pagination
     *
     * @throws OrmException
     */
    public function __construct(
        public readonly Driver $Driver,
        string                 $table_name,
        public readonly array  $conditions = [],
        public readonly array  $pagination = [],
    )
    {
        $this->TableMeta = $this->Driver->fetchTableMeta($table_name);
    }

    /**
     * Rewind the iterator to the first element.
     * @throws OrmException
     */
    public function rewind(): void
    {
        $this->position = 0;
        $this->reset();
        $this->currentModel = $this->fetchModel();
    }

    /**
     * Return the current Model element.
     *
     * @return Model|null
     */
    public function current(): Model|null
    {
        return $this->currentModel;
    }

    /**
     * Move forward to the next Model element.
     *
     * @return Model|null
     * @throws OrmException
     */
    #[ReturnTypeWillChange] public function next(): Model|null
    {
        $this->position++;
        $this->currentModel = $this->fetchModel();

        return $this->currentModel;
    }

    /**
     * Return the key of the current Model element.
     *
     * @return int
     */
    public function key(): int
    {
        return $this->position;
    }

    /**
     * Check if there is a current Model element after calls to rewind() or next().
     *
     * @return bool
     */
    public function valid(): bool
    {
        return $this->currentModel !== null;
    }

    /**
     * Fetch a Model instance from the PDO statement.
     *
     * @return Model|null
     *
     * @throws OrmException
     */
    protected function fetchModel(): null|Model
    {
        $stmt = $this->guardNullStatement();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        $model = new Model($this->Driver, $this->TableMeta->table_name);
        $model->set($row);

        return $model;
    }

    /**
     * Count the number of elements in the Query.
     *
     * @return int
     *
     * @throws OrmException
     */
    public function count(): int
    {
        $stmt = $this->guardNullStatement();

        return $stmt->rowCount();
    }

    /**
     * @return void
     */
    private function reset(): void
    {
        $this->statement = $this->Driver->fetchQueryIteratorStmt(
            $this->TableMeta->database_name,
            $this->TableMeta->table_name,
            $this->conditions,
            $this->pagination
        );
    }

    /**
     * @throws OrmException
     */
    private function guardNullStatement(): PDOStatement
    {
        if ($this->statement === null) {
            $this->reset();

            if ($this->statement === null) {
                throw new OrmException('how?');
            }
        }

        return $this->statement;
    }
}