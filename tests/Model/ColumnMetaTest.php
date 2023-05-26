<?php
declare(strict_types=1);

namespace Richbuilds\Orm\Tests\Model;

use Richbuilds\Orm\Model\ColumnMeta;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class ColumnMetaTest extends TestCase
{
    /**
     * @dataProvider providerForTestHumanize
     * @param string $input
     * @param string $expected
     * @return void
     */
    public function testHumanize(string $input, string $expected): void
    {
        $cm = new ColumnMeta('db', 'table', $input, 'int', 'int', false, true, 0, 1, 10, 1, 10, []);
        self::assertEquals($expected, $cm->humanize());
    }


    /**
     * @return array<int,array<string,string>>>
     */
    public static function providerForTestHumanize(): array
    {
        return [
            ['id','ID'],
            ['simple','Simple'],
            ['with_underscores', 'With Underscores']
        ];
    }
}
