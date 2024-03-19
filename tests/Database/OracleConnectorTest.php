<?php

namespace HyperfTest\Database\Oracle\Tests\Database;

use Hyperf\Database\Connectors\Connector;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Hyperf\Database\Oracle\Connectors\OracleConnector;
use PDO;

class OracleConnectorTest extends TestCase
{
    public function tearDown(): void
    {
        m::close();
    }

    public function testCreateConnection()
    {
        $connector = new OracleConnector();
        $config = [
            'host' => 'oracle',
            'port' => '1521',
            'service_name' => 'XE',
            'username' => 'userall',
            'password' => 'password',
        ];

        $pdo = $connector->connect($config);

        $this->assertInstanceOf(PDO::class, $pdo);
    }

    public function testOptionResolution()
    {
        $connector = new Connector;
        $connector->setDefaultOptions([0 => 'foo', 1 => 'bar']);
        $this->assertEquals([0 => 'baz', 1 => 'bar', 2 => 'boom'],
            $connector->getOptions(['options' => [0 => 'baz', 2 => 'boom']]));
    }
}
