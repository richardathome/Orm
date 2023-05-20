<?php
declare(strict_types=1);


namespace Richbuilds\Orm\Model;

use DateTime;
use Richbuilds\Orm\OrmException;
use RuntimeException;

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
    )
    {
    }

    /**
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
                'bigint', 'bit', 'tinyint', 'int', 'mediumint', 'smallint', 'year' => $this->toInt($value),
                'varchar', 'binary', 'blob', 'char', 'longblob', 'mediumblob', 'longtext', 'mediumtext', 'text', 'tinyblob', 'tinytext', 'varbinary' => $this->toVarchar($value),
                'date' => $this->toDate($value),
                'datetime' => $this->toDateTime($value),
                'decimal' => $this->toDecimal($value),
                'double' => $this->toDouble($value),
                'enum' => $this->toEnum($value),
                'float' => $this->toFloat($value),
                'json' => $this->toJson($value),
                'set' => $this->toSet($value),
                'time', 'timestamp' => $this->toTime($value),
                default => throw new RuntimeException(sprintf('unhandled column type %s', $this->data_type)),
            };

            return $value;

        } catch (OrmException $e) {
            throw new OrmException(sprintf('%s.%s.%s: %s', $this->database_name, $this->table_name, $this->column_name, $e->getMessage()));
        }
    }

    /**
     * @param mixed $value
     * @return mixed
     *
     * @throws OrmException
     */
    public function guardInRange(mixed $value): mixed
    {

        if ($value < $this->min_value || $value > $this->max_value) {
            throw new OrmException(sprintf('out of range for %s', $this->data_type));
        }

        return $value;
    }


    /**
     * @param mixed $value
     *
     * @return int|string
     *
     * @throws OrmException
     */
    private function toInt(mixed $value): int|string
    {
        $type = sprintf('%s %s', $this->is_signed ? 'signed' : 'unsigned', $this->data_type);

        if (!is_scalar($value)
            || preg_match('/^-?\d+$/', (string)$value) === 0) {
            throw new OrmException(sprintf('expected %s, got %s', $type, get_debug_type($value)));
        }

        $bc_value = (string)$value;

        if (bccomp($bc_value, (string)$this->min_value) < 0 || bccomp($bc_value, (string)$this->max_value) > 0) {
            throw new OrmException(sprintf('out of range for %s', $type));
        }

        if ((int)$value === $value) {
            return $value;
        }

        return $bc_value;
    }


    /**
     * @param mixed $value
     *
     * @return string
     *
     * @throws OrmException
     */
    public function toVarchar(mixed $value): string
    {
        $type = sprintf('%s(%s)', $this->data_type, $this->max_character_length);

        if (!is_scalar($value)) {
            throw new OrmException(sprintf('expected %s, got %s', $type, get_debug_type($value)));
        }

        $value = (string)$value;

        if (strlen($value) > $this->max_character_length) {
            throw new OrmException(sprintf('too long for %s', $type));
        }

        return $value;
    }


    /**
     * @param mixed $value
     *
     * @return Date
     *
     * @throws OrmException
     */
    public function toDate(mixed $value): Date
    {
        $type = $this->data_type;

        if (!$value instanceof Date) {
            throw new OrmException(sprintf('expected %s, got %s', $type, get_debug_type($value)));
        }

        $this->guardInRange($value);

        return $value;
    }


    /**
     * @throws OrmException
     */
    public function toDateTime(mixed $value): DateTime
    {
        $type = $this->data_type;

        if (is_scalar($value)) {
            $value = new DateTime((string)$value);
        }

        if (!$value instanceof DateTime) {
            throw new OrmException(sprintf('expected %s, got %s', $type, get_debug_type($value)));
        }

        $value = $this->guardInRange($value);

        return $value;
    }


    /**
     * @param mixed $value
     *
     * @return string
     *
     * @throws OrmException
     */
    public function toDecimal(mixed $value): string
    {
        $type = sprintf('%s %s(%s,%s)', $this->is_signed ? 'signed' : 'unsigned', $this->data_type, $this->precision, $this->scale);

        if (!is_scalar($value)) {
            throw new OrmException(sprintf('expected %s, got %s', $type, get_debug_type($value)));
        }

        $value = (string)$value;

        // Build the regex pattern based on the is_signed parameter, precision, and scale
        $pattern = $this->is_signed
            ? "/^-?\d{1,$this->precision}(?:\.\d{1,$this->scale})?$/"
            : "/^\d{1,$this->precision}(?:\.\d{1,$this->scale})?$/";

        // Check if the value matches the regex pattern
        if (preg_match($pattern, $value) === 0) {
            throw new OrmException(sprintf('out of range for %s', $type));
        }

        return $value;
    }


    /**
     * @param mixed $value
     *
     * @return string
     *
     * @throws OrmException
     */
    public function toDouble(mixed $value): string
    {
        $type = sprintf('%s %s(%s,%s)', $this->is_signed ? 'signed' : 'unsigned', $this->data_type, $this->precision, $this->scale);

        if (!is_numeric($value)) {
            throw new OrmException(sprintf('expected %s, got %s', $type, get_debug_type($value)));
        }

        $value = (string)$value;

        // Build the regex pattern based on the is_signed parameter
        $pattern = $this->is_signed ? '/^-?\d+(\.\d+)?$/i' : '/^\d+(\.\d+)?$/i';

        // Check if the value matches the regex pattern
        if (!preg_match($pattern, $value)) {
            throw new OrmException(sprintf('out of range for %s', $type));
        }

        $value = $this->guardInRange($value);

        return $value;
    }


    /**
     * @param mixed $value
     * @return string
     *
     * @throws OrmException
     */
    public function toEnum(mixed $value): string
    {
        $type = sprintf('%s', $this->column_type);

        if (!is_scalar($value)) {
            throw new OrmException(sprintf('expected %s, got %s', $type, get_debug_type($value)));
        }

        $value = (string)$value;

        if (!in_array($value, $this->options)) {
            throw new OrmException(sprintf('invalid %s value', $type));
        }

        return $value;
    }

    /**
     * @param mixed $value
     * @return float
     *
     * @throws OrmException
     */
    public function toFloat(mixed $value): float
    {
        $type = sprintf('%s %s(%s,%s)', $this->is_signed ? 'signed' : 'unsigned', $this->data_type, $this->precision, $this->scale);

        if (!is_numeric($value)) {
            throw new OrmException(sprintf('expected %s, got %s', $type, get_debug_type($value)));
        }

        $value = (float)$value;

        $value = $this->guardInRange($value);

        return $value;
    }


    /**
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

        if (!is_scalar($value)) {
            throw new OrmException(sprintf('expected %s, got %s', $type, get_debug_type($value)));
        }

        $value = json_decode((string)$value, true);

        if ($value === null) {
            throw new OrmException('invalid json');
        }


        return $value;
    }


    /**
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
            $value = [(string)$value];
        }

        if (!is_array($value)) {
            throw new OrmException(sprintf('expected %s, got %s', $type, get_debug_type($value)));
        }

        foreach ($value as $option) {
            if (!in_array($option, $this->options)) {
                throw new OrmException(sprintf('invalid value for %s', $type));
            }
        }

        return $value;
    }


    /**
     * @param mixed $value
     *
     * @return DateTime
     * @throws OrmException
     */
    public function toTime(mixed $value): DateTime
    {
        $type = $this->data_type;

        $tmp = $value;

        if (is_scalar($value)) {
            $tmp = DateTime::createFromFormat('H:i:s', (string)$value);
        }

        if (!$tmp instanceof DateTime) {
            throw new OrmException(sprintf('expected %s, got %s', $type, get_debug_type($value)));
        }

        $this->guardInRange($value);

        return $tmp;
    }

}