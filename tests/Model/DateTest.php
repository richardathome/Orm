<?php
declare(strict_types=1);

namespace Richbuilds\Orm\Tests\Model;

use Exception;
use PHPUnit\Framework\TestCase;
use Richbuilds\Orm\Model\Date;

/**
 *
 */
class DateTest extends TestCase
{

    /**
     * @return void
     * @throws Exception
     */
    public function testConstructWithValidDate(): void
    {
        $valid_date = '2023-05-18';
        $date = new Date($valid_date);

        self::assertInstanceOf(Date::class, $date);

        self::assertEquals($valid_date, $date->format('Y-m-d'));
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testConstructWithInvalidDate(): void
    {
        $invalid_date = 'invalid-date';

        self::expectException(Exception::class);
        self::expectExceptionMessage("Failed to parse time string ($invalid_date) at position 0");

        new Date($invalid_date);
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testToString(): void
    {
        $valid_date = '2023-05-18';
        $date = new Date($valid_date);

        self::assertEquals($valid_date, (string)$date);
    }
}
