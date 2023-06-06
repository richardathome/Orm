<?php
declare(strict_types=1);

namespace Richbuilds\Orm\Driver\MySqlDriver;

use DateTime;
use PDO;
use Richbuilds\Orm\Driver\Driver;
use Richbuilds\Orm\Model\ColumnMeta;
use Richbuilds\Orm\Model\Date;
use Richbuilds\Orm\Model\FkMeta;
use Richbuilds\Orm\Model\TableMeta;
use Richbuilds\Orm\OrmException;
use RuntimeException;

/**
 * Responsible for interacting with a mysql pdo datasource
 */
class MySqlDriver extends Driver
{
    private string $databaseName;

    /**
     * In request storage of any table metadata fetched
     *
     * @var array<string,array<string,TableMeta>> $tableMetaCache
     */
    private mixed $tableMetaCache = [];


    /**
     * @param PDO $pdo
     *
     * @throws OrmException
     */
    public function __construct(
        PDO $pdo
    ) {
        $this->guardValidDriver($pdo, 'mysql');

        parent::__construct($pdo, new MySqlQueryBuilder());

        $this->databaseName = (string)$this->fetchSqlColumn($this->QueryBuilder->buildFetchDatabaseName());

        if (empty($this->databaseName)) {
            throw new OrmException('no database selected');
        }
    }


    /**
     * @inheritDoc
     */
    public function fetchTableMeta(string $table_name): TableMeta
    {
        $database_name = $this->databaseName;

        if (!isset($this->tableMetaCache[$database_name][$table_name])) {
            $sql = $this->QueryBuilder->buildFetchTableMeta();
            $rows = $this->fetchSqlAll($sql, [
                'database_name' => $database_name,
                'table_name' => $table_name
            ]);

            if (empty($rows)) {
                throw new OrmException(sprintf(
                    'table %s not found in %s',
                    $table_name,
                    $this->databaseName
                ));
            }

            /**
             * @var array<string,ColumnMeta> $column_metas
             */
            $column_metas = [];

            /**
             * @var array<string> $pk_columns
             */
            $pk_columns = [];

            /**
             * @var array<string,FkMeta> $parents
             */
            $parents = [];

            /**
             * @var array<string,FkMeta> $parents
             */
            $children = [];

            foreach ($rows as $column_meta) {
                $column_name = (string)$column_meta['column_name'];

                if (!isset($column_metas[$column_name])) {
                    if ($column_meta['column_key'] === 'PRI') {
                        $pk_columns[] = $column_name;
                    }

                    $column_type = $column_meta['column_type'];
                    $data_type = $column_meta['data_type'];
                    $is_signed = !str_contains($column_meta['column_type'], ' unsigned')
                        || $data_type === 'bit';
                    $precision = $column_meta['precision'] ?? 0;
                    $scale = $column_meta['scale'] ?? 0;
                    $max_character_length = $column_meta['max_character_length'] ?? 0;

                    $min_value = $this->getMinValue($data_type, $is_signed, $max_character_length);
                    $max_value = $this->getMaxValue($data_type, $is_signed, $precision, $max_character_length);

                    $options = $this->getOptions($column_type);

                    $column_metas[$column_name] = new ColumnMeta(
                        $database_name,
                        $table_name,
                        $column_name,
                        $data_type,
                        $column_type,
                        $is_signed,
                        $column_meta['allow_null'] === 'YES',
                        $max_character_length,
                        $precision,
                        $scale,
                        $min_value,
                        $max_value,
                        $options
                    );
                }

                if ($column_meta['referenced_database'] !== null) {
                    $parents[$column_name] = new FkMeta(
                        $column_meta['referenced_database'],
                        $column_meta['referenced_table'],
                        $column_meta['referenced_column']
                    );
                }

                if ($column_meta['referring_database'] !== null) {
                    $key = (string)$column_meta['referring_table'];
                    $children[$key] = new FkMeta(
                        $column_meta['referring_database'],
                        $column_meta['referring_table'],
                        $column_meta['referring_column']
                    );
                }
            }

            $this->tableMetaCache[$database_name][$table_name] = new TableMeta(
                $database_name,
                $table_name,
                $column_metas,
                $pk_columns,
                $parents,
                $children
            );
        }

        return $this->tableMetaCache[$database_name][$table_name];
    }


    /**
     * Returns the smallest value this column can contain
     *
     * @param mixed $data_type
     * @param bool $is_signed
     * @param int $max_character_length
     *
     * @return mixed
     */
    private function getMinValue(mixed $data_type, bool $is_signed, int $max_character_length): mixed
    {

        // Check if the data type is a range type
        if ($max_character_length !== 0 || in_array($data_type, ['decimal', 'json'])) {
            return null;
        }

        if (!$is_signed) {
            return 0;
        }

        $min_values = [
            'bit' => 0,
            'bigint' => '-9223372036854775808',
            'int' => '-2147483648',
            'mediumint' => '-8388608',
            'smallint' => '-32768',
            'tinyint' => '-128',
            'date' => new Date('1000-01-01'),
            'datetime' => new DateTime('1000-01-01 00:00:00'),
            'time' => DateTime::createFromFormat('H:i:s', '00:00:00'),
            'timestamp' => DateTime::createFromFormat('Y-m-d H:i:s', '0000-00-00 00:00:00'),
            'double' => -1.79769E+308,
            'float' => -3.402823466E+38,
            'year' => 0,
        ];

        if (!isset($min_values[$data_type])) {
            // @codeCoverageIgnoreStart
            throw new RuntimeException(sprintf(
                'MySqlDriver::getMinValue(): unhandled type signed %s',
                $data_type
            ));
            // @codeCoverageIgnoreEnd
        }

        return $min_values[$data_type];
    }


    /**
     * Returns the largest value this column can contain
     *
     * @param string $data_type
     * @param bool $is_signed
     * @param int $precision
     * @param int $max_character_length
     *
     * @return mixed
     */
    private function getMaxValue(string $data_type, bool $is_signed, int $precision, int $max_character_length): mixed
    {
        // Check if the data type is a range type
        if ($max_character_length !== 0 || in_array($data_type, ['decimal', 'json'])) {
            return null;
        }

        // Define maximum values for signed and unsigned data types
        $max_values = [
            $is_signed => [
                'bit' => 2 ** $precision - 1,
                'bigint' => '9223372036854775807',
                'int' => 2147483647,
                'mediumint' => 8388607,
                'smallint' => 32767,
                'tinyint' => 127,
                'date' => new Date('9999-12-31'),
                'datetime' => new DateTime('9999-12-31 23:59:59'),
                'time' => DateTime::createFromFormat('H:i:s', '23:59:59'),
                'timestamp' => DateTime::createFromFormat('Y-m-d H:i:s', '9999-12-31 23:59:59'),
                'double' => 1.79769E+308,
                'float' => 3.402823466E+38,
                'year' => 9999
            ],
            !$is_signed => [
                'bigint' => '18446744073709551615',
                'int' => 4294967295,
                'mediumint' => 16777215,
                'smallint' => 65535,
                'tinyint' => 255,
                'double' => 1.79769E+308,
                'float' => 3.402823466E+38,
            ],
        ];

        // Check if the provided signed and data type combination is supported
        if (!isset($max_values[$is_signed][$data_type])) {
            // @codeCoverageIgnoreStart
            throw new RuntimeException(sprintf(
                'MySqlDriver::getMinValue(): unhandled data type: %s %s',
                $is_signed ? 'signed' : 'unsigned',
                $data_type
            ));
            // @codeCoverageIgnoreEnd
        }

        // Return the maximum value based on the signed and data type combination
        return $max_values[$is_signed][$data_type];
    }


    /**
     * Returns an array of valid options for an enum/set
     *
     * @param mixed $column_type
     *
     * @return array<string>
     */
    private function getOptions(mixed $column_type): array
    {
        preg_match('/^.*\((.*)\)$/', $column_type, $matches);

        if (empty($matches)) {
            // not an enum type
            return [];
        }

        $options = array_map(
            function ($option) {
                return trim($option, "'");
            },
            explode(',', $matches[1])
        );

        return $options;
    }


    /**
     * @inheritDoc
     */
    public function insert(TableMeta $table_meta, array $values = [], array $conditions = []): bool|string
    {
        $sql = $this->QueryBuilder->buildInsert($table_meta, $values, $conditions);

        $this->prepareAndExec($sql, $values);

        return $this->pdo->lastInsertId();
    }


    /**
     * @inheritDoc
     */
    public function update(TableMeta $table_meta, array $values = [], array $conditions = []): int
    {
        $sql = $this->QueryBuilder->buildUpdate($table_meta, $values, $conditions);

        $stmt = $this->prepareAndExec($sql, $values);

        return $stmt->rowCount();
    }


    /**
     * @inheritDoc
     */
    public function delete(TableMeta $table_meta, array $conditions): void
    {
        $sql = $this->QueryBuilder->buildDelete($table_meta, $conditions);

        $this->prepareAndExec($sql, $conditions);
    }
}
