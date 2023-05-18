<?php
declare(strict_types=1);

namespace Richbuilds\Orm\Tests\Model;

use DateTime;
use Richbuilds\Orm\Model\Date;
use Richbuilds\Orm\OrmException;

/**
 *
 */
class ModelSetGetTest extends ModelTestBase
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
    public function testGetSetSingleColumn(string $column_name, mixed $value, mixed $expected_value, string $expected_error): void {

        $model = $this->Orm->Model('datatypes');

        if (!empty($expected_error)) {
            self::expectExceptionMessage($expected_error);
        }

        $model->set($column_name, $value);

        if (empty($expected_error)) {
            self::assertEquals($expected_value, $model->get($column_name));
        }
    }

    /**
     * @return array<array{string,mixed,mixed,string}>
     */
    public static function providerForGetSetSingleColumn(): array
    {
        $minDate = new Date('1000-01-01');
        $maxDate = new Date('9999-12-31');
        $date = new Date();

        $minDateTime = new DateTime('1000-01-01 00:00:00');
        $maxDateTime = new DateTime('9999-12-31 23:59:59');
        $datetime = new DateTime();

        $time = DateTime::createFromFormat('H:i:s', (new DateTime())->format('H:i:s'));

        return [
            ['invalid_column', null, null, 'unknown column invalid_column in orm_test.datatypes'],

            ['allow_null', null, null, ''],
            ['not_null', 'foo', 'foo', ''],

            ['not_null', null, null, 'cannot be null'],
            ['not_null', 'foo', 'foo', ''],

            ['bigint_unsigned', 'foo', null, 'expected unsigned bigint, got string'],
            ['bigint_unsigned', -1, null, 'out of range for unsigned bigint'],
            ['bigint_unsigned', 1, '1', ''],

            ['bigint_signed', 'foo', null, 'expected signed bigint, got string'],
            ['bigint_signed', -1, '-1', ''],
            ['bigint_signed', 1, '1', ''],

            ['binary', $datetime, null, 'expected binary(8), got DateTime'],
            ['binary', '123456789', null, 'too long for binary(8)'],
            ['binary', '00000000', '00000000', ''],

            ['bit', $datetime, null, 'expected signed bit, got DateTime'],
            ['bit', -1, null, 'out of range for signed bit'],
            ['bit', 256, null, 'out of range for signed bit'],
            ['bit', 0, 0, ''],
            ['bit', 255, 255, ''],

            ['blob', $datetime, null, 'expected blob(65535), got DateTime'],
            ['blob', str_repeat('x', 65536), null, 'too long for blob(65535)'],
            ['blob', str_repeat('x', 100), str_repeat('x', 100), ''],

            ['boolean', $datetime, null, 'expected signed tinyint'],
            ['boolean', true, true, ''],
            ['boolean', 0, false, ''],

            ['char', $datetime, null, 'expected char(8), got DateTime'],
            ['char', '123456789', null, 'too long for char(8)'],
            ['char', 1, '1', ''],
            ['char', 'foo', 'foo', ''],

            ['date', 1, null, 'expected date, got int'],
            ['date', new Date('0999-01-01'), null, 'out of range for date'],
            ['date', $minDate, $minDate, ''],
            ['date', $maxDate, $maxDate, ''],
            ['date', $date, $date, ''],

            ['datetime', 1, null, 'expected datetime, got int'],
            ['datetime', new DateTime('0999-01-01'), null, 'out of range for date'],
            ['datetime', $minDateTime, $minDateTime, ''],
            ['datetime', $maxDateTime, $maxDateTime, ''],
            ['datetime', $datetime, $datetime, ''],

            ['decimal_unsigned', $datetime, null, 'expected unsigned decimal(3,2), got DateTime'],
            ['decimal_unsigned', 'foo', null, 'out of range for unsigned decimal(3,2)'],
            ['decimal_unsigned', -1, null, 'out of range for unsigned decimal(3,2)'],
            ['decimal_unsigned', 1000, null, 'out of range for unsigned decimal(3,2)'],
            ['decimal_unsigned', 99.999, null, 'out of range for unsigned decimal(3,2)'],
            ['decimal_unsigned', 0, '0', ''],
            ['decimal_unsigned', 999.99, '999.99', ''],

            ['decimal_signed', $datetime, null, 'expected signed decimal(3,2), got DateTime'],
            ['decimal_signed', -1000, null, 'out of range for signed decimal(3,2)'],
            ['decimal_signed', 1000, null, 'out of range for signed decimal(3,2)'],
            ['decimal_signed', -999.99, '-999.99', ''],
            ['decimal_signed', 999.99, '999.99', ''],

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

            ['int_unsigned', $datetime, null, 'expected unsigned int, got DateTime'],
            ['int_unsigned', -1, null, 'out of range for unsigned int'],
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

            ['mediumint_unsigned', $datetime, null, 'expected unsigned mediumint, got DateTime'],
            ['mediumint_unsigned', -1, null, 'out of range for unsigned mediumint'],
            ['mediumint_unsigned', 1, 1, ''],

            ['mediumint_signed', $datetime, null, 'expected signed mediumint, got DateTime'],
            ['mediumint_signed', -1, -1, ''],
            ['mediumint_signed', 0, 0, ''],

            ['mediumtext', $datetime, null, 'expected mediumtext(16777215), got DateTime'],
            ['mediumtext', 'foo', 'foo', ''],

            ['set', $datetime, null, "expected set('one','two','three'), got DateTime"],
            ['set', 'foo', null, "invalid value for set('one','two','three')"],
            ['set', 'one', ['one'], ''],
            ['set', ['one', 'three'], ['one', 'three'], ''],

            ['smallint_unsigned', $datetime, null, 'expected unsigned smallint, got DateTime'],
            ['smallint_unsigned', -1, null, 'out of range for unsigned smallint'],
            ['smallint_unsigned', 1, 1, ''],

            ['smallint_signed', $datetime, null, 'expected signed smallint, got DateTime'],
            ['smallint_signed', -1, -1, ''],
            ['smallint_signed', 1, 1, ''],

            ['text', $datetime, null, 'expected text(65535), got DateTime'],
            ['text', 'foo', 'foo', ''],

            ['time', 1, null, 'expected time, got int'],
            ['time', '99:99:99', null, 'out of range for time'],
            ['time', $time, $time, ''],

            ['timestamp', 1, null, 'expected timestamp, got int'],
            ['timestamp', $datetime, $datetime, ''],

            ['tinyblob', $datetime, null, 'expected tinyblob(255), got DateTime'],
            ['tinyblob', str_repeat('x', 256), null, 'too long for tinyblob(255)'],
            ['tinyblob', 'foo', 'foo', ''],

            ['tinyint_unsigned', $datetime, null, 'expected unsigned tinyint, got DateTime'],
            ['tinyint_unsigned', -1, null, 'out of range for unsigned tinyint'],
            ['tinyint_unsigned', 1, 1, ''],

            ['tinyint_signed', $datetime, null, 'expected signed tinyint, got DateTime'],
            ['tinyint_signed', -1, -1, ''],
            ['tinyint_signed', 1, 1, ''],

            ['tinytext', $datetime, null, 'expected tinytext(255), got DateTime'],
            ['tinytext', str_repeat('x', 256), null, 'too long for tinytext(255)'],
            ['tinytext', 'foo', 'foo', ''],

            ['varbinary', $datetime, null, 'expected varbinary(8), got DateTime'],
            ['varbinary', '123456789', null, 'too long for varbinary(8)'],
            ['varbinary', '12345678', '12345678', ''],

            ['varchar', $datetime, null, 'expected varchar(8), got DateTime'],
            ['varchar', '123456789', null, 'too long for varchar(8)'],
            ['varchar', '12345678', '12345678', ''],

            ['year', $datetime, null, 'expected signed year, got DateTime'],
            ['year', -1, null, 'out of range for signed year'],
            ['year', 1968, 1968, ''],
        ];
    }


    /**
     * @return void
     * @throws OrmException
     */
    public function testSetManyRollsBackOnError(): void {
        $user = $this->Orm->Model('users');
        $user->set('name','foo');

        self::expectExceptionMessage('expected varchar(45), got DateTime');
        $user->set([
            'id'=>1,
            'name'=>new DateTime()
        ]);

        self::assertEquals(['id'=>null,'name'=>'foo'], $user->get(['id','name']));
    }


    /**
     * @return void
     * @throws OrmException
     */
    public function testSetManyWorks(): void {
        $user = $this->Orm->Model('users');
        $user->set(['id'=>1,'name'=>'foo']);

        self::assertEquals(['id'=>1,'name'=>'foo'], $user->get(['id','name']));
    }


    /**
     * @return void
     * @throws OrmException
     */
    public function testGetWithNoParamsReturnsAllFields(): void {
        $user = $this->Orm->Model('users');

        self::assertEquals(['id'=>null,'name'=>null,'password'=>null], $user->get());
    }
}
