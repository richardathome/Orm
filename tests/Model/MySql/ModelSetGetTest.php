<?php
declare(strict_types=1);

namespace Richbuilds\Orm\Tests\Model\MySql;

use DateTime;
use Richbuilds\Orm\Model\Date;
use Richbuilds\Orm\OrmException;
use stdClass;

/**
 *
 */
class ModelSetGetTest extends MySqlTestBase
{

    /**
     * @dataProvider providerForGetSetSingleColumn
     *
     * @param string $column_name
     * @param mixed $value
     * @param mixed $expected_value
     * @param string $expected_error
     *
     * @return void
     *
     * @throws OrmException
     */
    public function testGetSetSingleColumn(
        string $column_name,
        mixed  $value,
        mixed  $expected_value,
        string $expected_error
    ): void {
        $model = self::$orm->model('datatypes');

        if (!empty($expected_error)) {
            self::expectExceptionMessage($expected_error);
        }

        $model->set($column_name, $value);

        if (empty($expected_error)) {
            self::assertEquals($expected_value, $model->get($column_name));

            $model->save();

            $reload = $model->fetchByPk($model->getPk());

            self::assertEquals($expected_value, $reload->get($column_name));
        }
    }

    /**
     * @return array<array{string,mixed,mixed,string}>
     */
    public static function providerForGetSetSingleColumn(): array
    {
        $min_date = new Date('1000-01-01');
        $max_date = new Date('9999-12-31');
        $date = new Date('2023-05-24');

        $min_datetime = new DateTime('1000-01-01 00:00:00');
        $max_datetime = new DateTime('9999-12-31 23:59:59');
        $datetime = new DateTime('2023-05-24 13:58:45');
        $datetime_string = $datetime->format('Y-m-d H:i:s');

        $time = '10:21:56';

        return [
            ['invalid_column', null, null, 'unknown column invalid_column in orm_test.datatypes'],

            ['allow_null', null, null, ''],
            ['not_null', 'foo', 'foo', ''],

            ['not_null', null, null, 'cannot be null'],
            ['not_null', 'foo', 'foo', ''],

            ['bigint_unsigned', 'foo', null, 'expected bigint unsigned, got string'],
            ['bigint_unsigned', -1, null, 'out of range for bigint unsigned'],
            ['bigint_unsigned', 1, '1', ''],

            ['bigint_signed', 'foo', null, 'expected bigint, got string'],
            ['bigint_signed', -1, '-1', ''],
            ['bigint_signed', 1, '1', ''],

            ['binary', $datetime, null, 'expected binary(8), got DateTime'],
            ['binary', '123456789', null, 'too long for binary(8)'],
            ['binary', '00000000', '00000000', ''],

            ['bit', $datetime, null, 'expected bit(8)'],
            ['bit', -1, null, 'out of range for bit(8)'],
            ['bit', 256, null, 'out of range for bit(8)'],
            ['bit', 0, 0, ''],
            ['bit', 1, 1, ''],

            ['blob', $datetime, null, 'expected blob(65535), got DateTime'],
            ['blob', str_repeat('x', 65536), null, 'too long for blob(65535)'],
            ['blob', str_repeat('x', 100), str_repeat('x', 100), ''],

            ['boolean', $datetime, null, 'expected tinyint, got DateTime'],
            ['boolean', true, true, ''],
            ['boolean', 0, false, ''],

            ['char', $datetime, null, 'expected char(8), got DateTime'],
            ['char', '123456789', null, 'too long for char(8)'],
            ['char', 1, '1', ''],
            ['char', 'foo', 'foo', ''],

            ['date', 1, null, 'could not convert to date'],
            ['date', new Date('0999-01-01'), null, 'out of range for date'],
            ['date', $min_date, $min_date, ''],
            ['date', $max_date, $max_date, ''],
            ['date', $date, $date, ''],

            ['datetime', 1, null, 'could not convert to DateTime'],
            ['datetime', new stdClass(), null, 'expected datetime, got stdClass'],
            ['datetime', new DateTime('0999-01-01'), null, 'out of range for date'],
            ['datetime', $min_datetime, $min_datetime, ''],
            ['datetime', $max_datetime, $max_datetime, ''],
            ['datetime', $datetime, $datetime, ''],
            ['datetime', $datetime_string, $datetime, ''],

            ['decimal_unsigned', $datetime, null, 'expected unsigned decimal(5,2), got DateTime'],
            ['decimal_unsigned', 'foo', null, 'out of range for unsigned decimal(5,2)'],
            ['decimal_unsigned', -1, null, 'out of range for unsigned decimal(5,2)'],
            ['decimal_unsigned', 1000, null, 'out of range for unsigned decimal(5,2)'],
            ['decimal_unsigned', '0.00', '0.00', ''],
            ['decimal_unsigned', 99.99, '99.99', ''],

            ['decimal_signed', $datetime, null, 'expected signed decimal(5,2), got DateTime'],
            ['decimal_signed', -1000, null, 'out of range for signed decimal(5,2)'],
            ['decimal_signed', 1000, null, 'out of range for signed decimal(5,2)'],
            ['decimal_signed', -99.99, '-99.99', ''],
            ['decimal_signed', 99.99, '99.99', ''],

            ['double_unsigned', $datetime, null, 'expected unsigned double(22,0), got DateTime'],
            ['double_unsigned', -1, null, 'out of range for unsigned double(22,0)'],
            ['double_unsigned', 22.2, 22.2, ''],

            ['double_signed', $datetime, null, 'expected signed double(22,0), got DateTime'],
            ['double_signed', -1, -1, ''],

            ['enum', $datetime, null, "expected enum('one','two','three'), got DateTime"],
            ['enum', 'invalid', null, "invalid enum('one','two','three') value"],
            ['enum', 'one', 'one', ''],

            ['float', $datetime, null, 'expected signed float(12,0), got DateTime'],
            ['float', -1, -1.0, ''],

            ['int_unsigned', $datetime, null, 'expected int unsigned, got DateTime'],
            ['int_unsigned', -1, null, 'out of range for int unsigned'],
            ['int_unsigned', 0, 0, ''],

            ['longblob', $datetime, null, 'expected longblob(4294967295), got DateTime'],
            ['longblob', 'foo', 'foo', ''],

            ['mediumblob', $datetime, null, 'expected mediumblob(16777215), got DateTime'],
            ['mediumblob', 'foo', 'foo', ''],

            ['json', $datetime, null, 'expected json, got DateTime'],
            ['json', 'invalid-json', null, 'invalid json'],
            ['json', ['foo' => 1], ['foo' => 1], ''],
            ['json', '{"foo": 1}', ['foo' => 1], ''],

            ['longtext', $datetime, null, 'expected longtext(4294967295), got DateTime'],
            ['longtext', 'foo', 'foo', ''],

            ['mediumint_unsigned', $datetime, null, 'expected mediumint unsigned, got DateTime'],
            ['mediumint_unsigned', -1, null, 'out of range for mediumint unsigned'],
            ['mediumint_unsigned', 1, 1, ''],

            ['mediumint_signed', $datetime, null, 'expected mediumint, got DateTime'],
            ['mediumint_signed', -1, -1, ''],
            ['mediumint_signed', 0, 0, ''],

            ['mediumtext', $datetime, null, 'expected mediumtext(16777215), got DateTime'],
            ['mediumtext', 'foo', 'foo', ''],

            ['set', $datetime, null, "expected set('one','two','three'), got DateTime"],
            ['set', 'foo', null, "invalid value for set('one','two','three')"],
            ['set', 'one', ['one'], ''],
            ['set', ['one', 'three'], ['one', 'three'], ''],

            ['smallint_unsigned', $datetime, null, 'expected smallint unsigned, got DateTime'],
            ['smallint_unsigned', -1, null, ' out of range for smallint unsigned'],
            ['smallint_unsigned', 1, 1, ''],

            ['smallint_signed', $datetime, null, 'expected smallint, got DateTime'],
            ['smallint_signed', -1, -1, ''],
            ['smallint_signed', 1, 1, ''],

            ['text', $datetime, null, 'expected text(65535), got DateTime'],
            ['text', 'foo', 'foo', ''],

            ['time', 1, null, 'invalid time format'],
            ['time', '99:99:99', null, 'invalid time format'],
            ['time', $time, $time, ''],

            ['timestamp', 1, null, 'could not convert to DateTime'],
            ['timestamp', $datetime, $datetime, ''],

            ['tinyblob', $datetime, null, 'expected tinyblob(255), got DateTime'],
            ['tinyblob', str_repeat('x', 256), null, 'too long for tinyblob(255)'],
            ['tinyblob', 'foo', 'foo', ''],

            ['tinyint', $datetime, null, 'expected tinyint, got DateTime'],
            ['tinyint', -1, 1, ''],
            ['tinyint', 0, 0, ''],
            ['tinyint', 1, 1, ''],
            ['tinyint',true,1,''],
            ['tinyint',false,0,''],

            ['tinytext', $datetime, null, 'expected tinytext(255), got DateTime'],
            ['tinytext', str_repeat('x', 256), null, 'too long for tinytext(255)'],
            ['tinytext', 'foo', 'foo', ''],

            ['varbinary', $datetime, null, 'expected varbinary(8), got DateTime'],
            ['varbinary', '123456789', null, 'too long for varbinary(8)'],
            ['varbinary', '12345678', '12345678', ''],

            ['varchar', $datetime, null, 'expected varchar(8), got DateTime'],
            ['varchar', '123456789', null, 'too long for varchar(8)'],
            ['varchar', '12345678', '12345678', ''],

            ['year', $datetime, null, 'expected year, got DateTime'],
            ['year', -1, null, 'out of range for year'],
            ['year', 1968, 1968, ''],
        ];
    }


    /**
     * @return void
     * @throws OrmException
     */
    public function testSetManyRollsBackOnError(): void
    {
        $user = self::$orm->model('users');
        $user->set('name', 'foo');

        self::expectExceptionMessage('expected varchar(45), got DateTime');
        $user->set([
            'id' => 1,
            'name' => new DateTime()
        ]);

        self::assertEquals(['id' => null, 'name' => 'foo'], $user->get(['id', 'name']));
    }


    /**
     * @return void
     * @throws OrmException
     */
    public function testSetManyWorks(): void
    {
        $user = self::$orm->model('users');
        $user->set(['id' => 1, 'name' => 'foo']);

        self::assertEquals(['id' => 1, 'name' => 'foo'], $user->get(['id', 'name']));
    }


    /**
     * @return void
     * @throws OrmException
     */
    public function testGetWithNoParamsReturnsAllFields(): void
    {
        $user = self::$orm->model('users');

        self::assertEquals(['id' => null, 'name' => null, 'password' => null], $user->get());
    }
}
