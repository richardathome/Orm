<?php
declare(strict_types=1);

namespace Richbuilds\Orm\Tests\Model;

use Exception;
use Richbuilds\Orm\Model\Date;
use PHPUnit\Framework\TestCase;

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
        $validDate = '2023-05-18';
        $date = new Date($validDate);
        self::assertInstanceOf(Date::class, $date);
        self::assertEquals($validDate, $date->format('Y-m-d'));
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testConstructWithInvalidDate(): void
    {
        $invalidDate = 'invalid-date';

        self::expectException(Exception::class);
        self::expectExceptionMessage("Failed to parse time string ($invalidDate) at position 0");

        new Date($invalidDate);
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testToString(): void
    {
        $validDate = '2023-05-18';
        $date = new Date($validDate);
        self::assertEquals($validDate, (string)$date);
    }
}
