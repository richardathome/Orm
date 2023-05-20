<?php
declare(strict_types=1);

namespace Richbuilds\Orm\Tests\Model;

use PDO;
use PHPUnit\Framework\TestCase;
use Richbuilds\Orm\Orm;
use Richbuilds\Orm\OrmException;

/**
 *
 */
class OrmTestBase extends TestCase
{
    protected Orm $Orm;

    /**
     * @inheritDoc
     *
     * @throws OrmException
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->Orm = new Orm(new PDO('mysql:host=localhost;dbname=orm_test', 'test', 'test'));
    }

}
