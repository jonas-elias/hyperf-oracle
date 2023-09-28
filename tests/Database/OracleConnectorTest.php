<?php

namespace HyperfTest\Database\Oracle\Tests\Database;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Hyperf\Database\Oracle\Connectors\OracleConnector;

class OracleConnectorTest extends TestCase
{
    public function tearDown(): void
    {
        m::close();
    }
}
