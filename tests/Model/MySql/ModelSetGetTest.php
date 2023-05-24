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
    public function testGetSetSingleColumn(string $column_name, mixed $value, mixed $expected_value, string $expected_error): void
    {
        $model = self::$Orm->Model('datatypes');

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
        $minDate = new Date('1000-01-01');
        $maxDate = new Date('9999-12-31');
        $date = new Date('2023-05-24');

        $minDateTime = new DateTime('1000-01-01 00:00:00');
        $maxDateTime = new DateTime('9999-12-31 23:59:59');
        $datetime = new DateTime('2023-05-24 13:58:45');
        $datetime_string = $datetime->format('Y-m-d H:i:s');

        $time = '10:21:56';

        return [
            0 => ['invalid_column', null, null, 'unknown column invalid_column in orm_test.datatypes'],

            1 => ['allow_null', null, null, ''],
            2 => ['not_null', 'foo', 'foo', ''],

            3 => ['not_null', null, null, 'cannot be null'],
            4 => ['not_null', 'foo', 'foo', ''],

            5 => ['bigint_unsigned', 'foo', null, 'expected unsigned bigint, got string'],
            6 => ['bigint_unsigned', -1, null, 'out of range for unsigned bigint'],
            7 => ['bigint_unsigned', 1, '1', ''],

            8 => ['bigint_signed', 'foo', null, 'expected signed bigint, got string'],
            9 => ['bigint_signed', -1, '-1', ''],
            10 => ['bigint_signed', 1, '1', ''],

            11 => ['binary', $datetime, null, 'expected binary(8), got DateTime'],
            12 => ['binary', '123456789', null, 'too long for binary(8)'],
            13 => ['binary', '00000000', '00000000', ''],

            14 => ['bit', $datetime, null, 'expected signed bit, got DateTime'],
            15 => ['bit', -1, null, 'out of range for signed bit'],
            16 => ['bit', 256, null, 'out of range for signed bit'],
            17 => ['bit', 0, 0, ''],
            18 => ['bit', 1, 1, ''],

            19 => ['blob', $datetime, null, 'expected blob(65535), got DateTime'],
            20 => ['blob', str_repeat('x', 65536), null, 'too long for blob(65535)'],
            21 => ['blob', str_repeat('x', 100), str_repeat('x', 100), ''],

            22 => ['boolean', $datetime, null, 'expected signed tinyint'],
            23 => ['boolean', true, true, ''],
            24 => ['boolean', 0, false, ''],

            25 => ['char', $datetime, null, 'expected char(8), got DateTime'],
            26 => ['char', '123456789', null, 'too long for char(8)'],
            27 => ['char', 1, '1', ''],
            28 => ['char', 'foo', 'foo', ''],

            29 => ['date', 1, null, 'could not convert to date'],
            30 => ['date', new Date('0999-01-01'), null, 'out of range for date'],
            31 => ['date', $minDate, $minDate, ''],
            32 => ['date', $maxDate, $maxDate, ''],
            33 => ['date', $date, $date, ''],

            34 => ['datetime', 1, null, 'could not convert to DateTime'],
            35 => ['datetime', new stdClass(), null, 'expected datetime, got stdClass'],
            36 => ['datetime', new DateTime('0999-01-01'), null, 'out of range for date'],
            37 => ['datetime', $minDateTime, $minDateTime, ''],
            38 => ['datetime', $maxDateTime, $maxDateTime, ''],
            39 => ['datetime', $datetime, $datetime, ''],
            40 => ['datetime', $datetime_string, $datetime, ''],

            41 => ['decimal_unsigned', $datetime, null, 'expected unsigned decimal(5,2), got DateTime'],
            42 => ['decimal_unsigned', 'foo', null, 'out of range for unsigned decimal(5,2)'],
            43 => ['decimal_unsigned', -1, null, 'out of range for unsigned decimal(5,2)'],
            44 => ['decimal_unsigned', 1000, null, 'out of range for unsigned decimal(5,2)'],
            45 => ['decimal_unsigned', '0.00', '0.00', ''],
            46 => ['decimal_unsigned', 99.99, '99.99', ''],

            47 => ['decimal_signed', $datetime, null, 'expected signed decimal(5,2), got DateTime'],
            48 => ['decimal_signed', -1000, null, 'out of range for signed decimal(5,2)'],
            49 => ['decimal_signed', 1000, null, 'out of range for signed decimal(5,2)'],
            50 => ['decimal_signed', -99.99, '-99.99', ''],
            51 => ['decimal_signed', 99.99, '99.99', ''],

            52 => ['double_unsigned', $datetime, null, 'expected unsigned double(22,0), got DateTime'],
            53 => ['double_unsigned', -1, null, 'out of range for unsigned double(22,0)'],
            54 => ['double_unsigned', 22.2, 22.2, ''],

            55 => ['double_signed', $datetime, null, 'expected signed double(22,0), got DateTime'],
            56 => ['double_signed', -1, -1, ''],

            57 => ['enum', $datetime, null, "expected enum('one','two','three'), got DateTime"],
            58 => ['enum', 'invalid', null, "invalid enum('one','two','three') value"],
            59 => ['enum', 'one', 'one', ''],

            60 => ['float', $datetime, null, 'expected signed float(12,0), got DateTime'],
            61 => ['float', -1, -1.0, ''],

            62 => ['int_unsigned', $datetime, null, 'expected unsigned int, got DateTime'],
            63 => ['int_unsigned', -1, null, 'out of range for unsigned int'],
            64 => ['int_unsigned', 0, 0, ''],

            65 => ['longblob', $datetime, null, 'expected longblob(4294967295), got DateTime'],
            66 => ['longblob', 'foo', 'foo', ''],

            67 => ['mediumblob', $datetime, null, 'expected mediumblob(16777215), got DateTime'],
            68 => ['mediumblob', 'foo', 'foo', ''],

            69 => ['json', $datetime, null, 'expected json, got DateTime'],
            70 => ['json', 'invalid-json', null, 'invalid json'],
            71 => ['json', ['foo' => 1], ['foo' => 1], ''],
            72 => ['json', '{"foo": 1}', ['foo' => 1], ''],

            73 => ['longtext', $datetime, null, 'expected longtext(4294967295), got DateTime'],
            74 => ['longtext', 'foo', 'foo', ''],

            75 => ['mediumint_unsigned', $datetime, null, 'expected unsigned mediumint, got DateTime'],
            76 => ['mediumint_unsigned', -1, null, 'out of range for unsigned mediumint'],
            77 => ['mediumint_unsigned', 1, 1, ''],

            78 => ['mediumint_signed', $datetime, null, 'expected signed mediumint, got DateTime'],
            79 => ['mediumint_signed', -1, -1, ''],
            80 => ['mediumint_signed', 0, 0, ''],

            81 => ['mediumtext', $datetime, null, 'expected mediumtext(16777215), got DateTime'],
            82 => ['mediumtext', 'foo', 'foo', ''],

            83 => ['set', $datetime, null, "expected set('one','two','three'), got DateTime"],
            84 => ['set', 'foo', null, "invalid value for set('one','two','three')"],
            85 => ['set', 'one', ['one'], ''],
            86 => ['set', ['one', 'three'], ['one', 'three'], ''],

            87 => ['smallint_unsigned', $datetime, null, 'expected unsigned smallint, got DateTime'],
            88 => ['smallint_unsigned', -1, null, 'out of range for unsigned smallint'],
            89 => ['smallint_unsigned', 1, 1, ''],

            90 => ['smallint_signed', $datetime, null, 'expected signed smallint, got DateTime'],
            91 => ['smallint_signed', -1, -1, ''],
            92 => ['smallint_signed', 1, 1, ''],

            93 => ['text', $datetime, null, 'expected text(65535), got DateTime'],
            94 => ['text', 'foo', 'foo', ''],

            95 => ['time', 1, null, 'invalid time format'],
            96 => ['time', '99:99:99', null, 'invalid time format'],
            97 => ['time', $time, $time, ''],

            98=>['timestamp', 1, null, 'could not convert to DateTime'],
            99=>['timestamp', $datetime, $datetime, ''],

            100=>['tinyblob', $datetime, null, 'expected tinyblob(255), got DateTime'],
            101=>['tinyblob', str_repeat('x', 256), null, 'too long for tinyblob(255)'],
            102=>['tinyblob', 'foo', 'foo', ''],

            103=>['tinyint_unsigned', $datetime, null, 'expected unsigned tinyint, got DateTime'],
            104=>['tinyint_unsigned', -1, null, 'out of range for unsigned tinyint'],
            105=>['tinyint_unsigned', 1, 1, ''],

            106=>['tinyint_signed', $datetime, null, 'expected signed tinyint, got DateTime'],
            107=>['tinyint_signed', -1, -1, ''],
            108=>['tinyint_signed', 1, 1, ''],

            109=>['tinytext', $datetime, null, 'expected tinytext(255), got DateTime'],
            110=>['tinytext', str_repeat('x', 256), null, 'too long for tinytext(255)'],
            111=>['tinytext', 'foo', 'foo', ''],

            112=>['varbinary', $datetime, null, 'expected varbinary(8), got DateTime'],
            113=>['varbinary', '123456789', null, 'too long for varbinary(8)'],
            114=>['varbinary', '12345678', '12345678', ''],

            115=>['varchar', $datetime, null, 'expected varchar(8), got DateTime'],
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
    public function testSetManyRollsBackOnError(): void
    {
        $user = self::$Orm->Model('users');
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
        $user = self::$Orm->Model('users');
        $user->set(['id' => 1, 'name' => 'foo']);

        self::assertEquals(['id' => 1, 'name' => 'foo'], $user->get(['id', 'name']));
    }


    /**
     * @return void
     * @throws OrmException
     */
    public function testGetWithNoParamsReturnsAllFields(): void
    {
        $user = self::$Orm->Model('users');

        self::assertEquals(['id' => null, 'name' => null, 'password' => null], $user->get());
    }

}
