<?php
declare(strict_types=1);


namespace Richbuilds\Orm\Model;

use DateTime;
use Richbuilds\Orm\OrmException;
use RuntimeException;
use Throwable;

/**
 * Holds meta information about a database column
 */
class ColumnMeta
{
    /**
     * @param string $database_name
     * @param string $table_name
     * @param string $column_name
     * @param string $data_type
     * @param string $column_type
     * @param bool $is_signed
     * @param bool $allow_null
     * @param int $max_character_length
     * @param int $precision
     * @param int $scale
     * @param mixed $min_value
     * @param mixed $max_value
     * @param array<string> $options
     */
    public function __construct(
        public readonly string $database_name,
        public readonly string $table_name,
        public readonly string $column_name,
        public readonly string $data_type,
        public readonly string $column_type,
        public readonly bool   $is_signed,
        public readonly bool   $allow_null,
        public int             $max_character_length,
        public int             $precision,
        public int             $scale,
        public mixed           $min_value,
        public mixed           $max_value,
        public array           $options
    ) {
    }


    /**
     * @return string
     */
    public function humanize(): string
    {
        $human = $this->column_name;

        $human = str_replace('_', ' ', $human);
        $human = ucwords($human);

        if ($human == 'Id') {
            $human = 'ID';
        }

        return $human;
    }

    /**
     * Checks $value is valid for it's mysql type and cast it to its PHP type
     *
     * @param mixed $value
     *
     * @return mixed
     *
     * @throws OrmException
     */
    public function toPhp(mixed $value): mixed
    {
        try {
            if ($value === null) {
                if (!$this->allow_null) {
                    throw new OrmException('cannot be null');
                }

                return null;
            }

            $value = match ($this->data_type) {
                'tinyint' => $this->toTinyInt($value),
                'int', 'smallint', 'mediumint', 'bigint',
                'bit', 'year'
                => $this->toInt($value),
                'char', 'varchar',
                'blob', 'tinyblob', 'mediumblob', 'longblob',
                'longtext', 'mediumtext', 'text', 'tinytext',
                'binary', 'varbinary'
                => $this->toVarchar($value),
                'date' => $this->toDate($value),
                'datetime', 'timestamp' => $this->toDateTime($value),
                'decimal' => $this->toDecimal($value),
                'double' => $this->toDouble($value),
                'enum' => $this->toEnum($value),
                'float' => $this->toFloat($value),
                'json' => $this->toJson($value),
                'set' => $this->toSet($value),
                'time' => $this->toTime($value),
                default => throw new RuntimeException(sprintf(
                    'unhandled column type %s',
                    $this->data_type
                )),
            };

            return $value;
        } catch (OrmException $e) {
            throw new OrmException(sprintf(
                '%s.%s.%s: %s',
                $this->database_name,
                $this->table_name,
                $this->column_name,
                $e->getMessage()
            ));
        }
    }




    /**
     * Checks $value is valid for a mysql Int and cast it to a PHP int
     *
     * @param mixed $value
     *
     * @return int|string
     *
     * @throws OrmException
     */
    private function toInt(mixed $value): int|string
    {
        $type = sprintf(
            '%s %s',
            $this->is_signed ? 'signed' : 'unsigned',
            $this->data_type
        );

        $type = $this->column_type;

        $this->guard(
            !is_scalar($value)
            || preg_match('/^-?\d+$/', (string)$value) === 0,
            sprintf(
                'expected %s, got %s',
                $type,
                get_debug_type($value)
            )
        );

        $bc_value = (string)$value;

        $this->guard(
            bccomp($bc_value, (string)$this->min_value) < 0 || bccomp($bc_value, (string)$this->max_value) > 0,
            sprintf(
                'out of range for %s',
                $type
            )
        );

        if ((int)$value === $value) {
            return $value;
        }

        return $bc_value;
    }


    /**
     * Checks $value is valid for a mysql Varchar and cast it to a PHP string
     *
     * @param mixed $value
     *
     * @return string
     *
     * @throws OrmException
     */
    public function toVarchar(mixed $value): string
    {
        $type = sprintf('%s(%s)', $this->data_type, $this->max_character_length);

        $this->guardIsScalar($value, $type);

        $value = (string)$value;

        $this->guard(
            strlen($value) > $this->max_character_length,
            sprintf(
                'too long for %s',
                $type
            )
        );

        return $value;
    }


    /**
     * Checks $value is valid for a mysql Date and cast it to a PHP Date
     *
     * @param mixed $value
     *
     * @return Date
     *
     * @throws OrmException
     */
    public function toDate(mixed $value): Date
    {
        $type = $this->data_type;

        if (is_scalar($value)) {
            try {
                $value = new Date((string)$value);
            } catch (Throwable) {
                throw new OrmException(sprintf(
                    'could not convert to %s',
                    $type
                ));
            }
        }

        $this->guard(
            !$value instanceof Date,
            sprintf(
                'expected %s, got %s',
                $type,
                get_debug_type($value)
            )
        );

        $value = $this->guardInRange($value);

        return $value;
    }


    /**
     * Checks $value is valid for a mysql Datetime and cast it to a PHP DateTime
     *
     * @param mixed $value
     *
     * @return DateTime
     *
     * @throws OrmException
     */
    public function toDateTime(mixed $value): DateTime
    {
        $type = $this->data_type;

        if (is_scalar($value)) {
            try {
                $value = new DateTime((string)$value);
            } catch (Throwable) {
                throw new OrmException('could not convert to DateTime');
            }
        }

        $this->guard(
            !$value instanceof DateTime,
            sprintf(
                'expected %s, got %s',
                $type,
                get_debug_type($value)
            )
        );

        $value = $this->guardInRange($value);

        return $value;
    }


    /**
     * Checks $value is valid for a mysql Decimal and casts it to a PHP string
     *
     * @param mixed $value
     *
     * @return string
     *
     * @throws OrmException
     */
    public function toDecimal(mixed $value): string
    {
        $type = sprintf(
            '%s %s(%s,%s)',
            $this->is_signed ? 'signed' : 'unsigned',
            $this->data_type,
            $this->precision,
            $this->scale
        );

        $this->guardIsScalar($value, $type);

        $value = (string)$value;

        $p = $this->precision - $this->scale;

        // Build the regex pattern based on the is_signed parameter, precision, and scale
        $pattern = $this->is_signed
            ? "/^-?\d{1,$p}(?:\.\d{1,$this->scale})?$/"
            : "/^\d{1,$p}(?:\.\d{1,$this->scale})?$/";

        $this->guard(
            preg_match($pattern, $value) === 0,
            sprintf(
                'out of range for %s',
                $type
            )
        );

        return $value;
    }


    /**
     * Checks $value is valid for a mysql Double and casts it to a PHP string
     *
     * @param mixed $value
     *
     * @return string
     *
     * @throws OrmException
     */
    public function toDouble(mixed $value): string
    {
        $type = sprintf(
            '%s %s(%s,%s)',
            $this->is_signed ? 'signed' : 'unsigned',
            $this->data_type,
            $this->precision,
            $this->scale
        );

        $this->guard(
            !is_numeric($value),
            sprintf(
                'expected %s, got %s',
                $type,
                get_debug_type($value)
            )
        );

        $value = (string)$value;

        // Build the regex pattern based on the is_signed parameter
        $pattern = $this->is_signed ? '/^-?\d+(\.\d+)?$/i' : '/^\d+(\.\d+)?$/i';

        $this->guard(
            !preg_match($pattern, $value),
            sprintf(
                'out of range for %s',
                $type
            )
        );

        $value = $this->guardInRange($value);

        return $value;
    }


    /**
     * Checks $value is valid for a mysql Enum and casts it to a PHP string
     *
     * @param mixed $value
     * @return string
     *
     * @throws OrmException
     */
    public function toEnum(mixed $value): string
    {
        $type = sprintf('%s', $this->column_type);

        $this->guardIsScalar($value, $type);

        $value = (string)$value;

        $this->guard(
            !in_array($value, $this->options),
            sprintf(
                'invalid %s value',
                $type
            )
        );

        return $value;
    }


    /**
     * Checks $value is valid for a mysql Float and cast it to a PHP float
     *
     * @param mixed $value
     * @return float
     *
     * @throws OrmException
     */
    public function toFloat(mixed $value): float
    {
        $type = sprintf(
            '%s %s(%s,%s)',
            $this->is_signed ? 'signed' : 'unsigned',
            $this->data_type,
            $this->precision,
            $this->scale
        );

        $this->guard(
            !is_numeric($value),
            sprintf(
                'expected %s, got %s',
                $type,
                get_debug_type($value)
            )
        );

        $value = (float)$value;

        $value = $this->guardInRange($value);

        return $value;
    }


    /**
     * Checks $value is valid for a mysql Json and cast it to a PHP array
     *
     * @param mixed $value
     *
     * @return array<string,mixed>
     *
     * @throws OrmException
     */
    public function toJson(mixed $value): array
    {
        $type = $this->data_type;

        if (is_array($value)) {
            return $value;
        }

        $this->guardIsScalar($value, $type);

        $value = json_decode((string)$value, true);

        $this->guard(
            $value === null,
            'invalid json'
        );

        return $value;
    }


    /**
     * Checks $value is valid for a mysql Set and cast it to a PHP array
     *
     * @param mixed $value
     *
     * @return array<string,mixed>
     *
     * @throws OrmException
     */
    public function toSet(mixed $value): array
    {
        $type = $this->column_type;

        if (is_scalar($value)) {
            $value = explode(',', (string)$value);
        }

        $this->guard(
            !is_array($value),
            sprintf(
                'expected %s, got %s',
                $type,
                get_debug_type($value)
            )
        );

        foreach ($value as $option) {
            $this->guard(
                !in_array($option, $this->options),
                sprintf(
                    'invalid value for %s',
                    $type
                )
            );
        }

        return $value;
    }


    /**
     * Checks $value is valid for a mysql Time and casts it to a PHP DateTime
     *
     * @param mixed $value
     *
     * @return string
     *
     * @throws OrmException
     */
    public function toTime(mixed $value): string
    {
        $type = $this->data_type;

        $this->guardIsScalar($value, $type);

        $value = (string)$value;

        $this->guard(
            !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d:[0-5]\d$/', $value),
            'invalid time format'
        );

        return $value;
    }

    /**
     * @param mixed $value
     *
     * @return int|string|float|null
     */
    public function toSql(mixed $value): null|int|string|float
    {
        return match ($this->data_type) {
            'int', 'tinyint', 'smallint', 'mediumint', 'bigint',
            'varchar', 'char',
            'binary', 'varbinary',
            'decimal', 'double', 'float',
            'enum',
            'blob', 'tinyblob', 'mediumblob', 'longblob',
            'text', 'tinytext', 'mediumtext', 'longtext',
            'year',
            => $value,
            'time' => (string)$value,
            'bit' => $value ? chr(1) : chr(0),
            'datetime', 'timestamp' => $value->format('Y-m-d H:i:s'),
            'date' => $value->format('Y-m-d'),
            'json' => json_encode($value),
            'set' => join(',', $value),
            default => throw new RuntimeException('unhandled type ' . $this->data_type)
        };
    }

    /**
     * @param mixed $value
     *
     * @return int
     *
     * @throws OrmException
     */
    private function toTinyInt(mixed $value): int
    {
        $type = $this->data_type;

        $this->guardIsScalar($value, $type);

        return $value ? 1 : 0;
    }

    /**
     * @param bool $expression
     *
     * @param string $message
     *
     * @return void
     *
     * @throws OrmException
     */
    private function guard(bool $expression, string $message): void
    {
        if ($expression) {
            throw new OrmException($message);
        }
    }

    /**
     * @param mixed $value
     *
     * @param string $type
     *
     * @return void
     *
     * @throws OrmException
     */
    private function guardIsScalar(mixed $value, string $type): void
    {
        $this->guard(
            !is_scalar($value),
            sprintf(
                'expected %s, got %s',
                $type,
                get_debug_type($value)
            )
        );
    }

    /**
     * Checks $value is in range for this column type
     *
     * @param mixed $value
     * @return mixed
     *
     * @throws OrmException
     */
    private function guardInRange(mixed $value): mixed
    {
        $this->guard(
            $value < $this->min_value || $value > $this->max_value,
            sprintf(
                'out of range for %s',
                $this->data_type
            )
        );


        return $value;
    }
}
