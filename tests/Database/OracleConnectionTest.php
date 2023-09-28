<?php

namespace HyperfTest\Database\Oracle\Tests\Database;

use Mockery as m;
use PDO;
use PHPUnit\Framework\TestCase;

class OracleConnectionTest extends TestCase
{
    public function tearDown(): void
    {
        m::close();
    }
}
