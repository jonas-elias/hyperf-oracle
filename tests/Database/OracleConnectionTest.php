<?php

namespace HyperfTest\Database\Oracle\Tests\Database;

use Hyperf\Database\Oracle\OracleSqlConnection;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class OracleConnectionTest extends TestCase
{
    public function tearDown(): void
    {
        m::close();
    }

    public function testCreateSequence()
    {
        $connection = m::mock(OracleSqlConnection::class);
        $connection->shouldReceive('createSequence')->with('posts_id_seq')->once()->andReturn(true);
        $this->assertEquals(true, $connection->createSequence('posts_id_seq'));
    }

    public function testCreateSequenceInvalidName()
    {
        $connection = m::mock(OracleSqlConnection::class);
        $connection->shouldReceive('createSequence')->with(null)->once()->andReturn(false);
        $this->assertEquals(false, $connection->createSequence(null));
    }

    public function testDropSequence()
    {
        $connection = m::mock(OracleSqlConnection::class);
        $connection->shouldReceive('dropSequence')->with('posts_id_seq')->once()->andReturn(true);
        $connection->shouldReceive('checkSequence')->with('posts_id_seq')->once()->andReturn(true);
        $connection->checkSequence('posts_id_seq');
        $this->assertEquals(true, $connection->dropSequence('posts_id_seq'));
    }

    public function testDropSequenceInvalidName()
    {
        $connection = m::mock(OracleSqlConnection::class);
        $connection->shouldReceive('dropSequence')->with(null)->once()->andReturn(false);
        $connection->shouldReceive('checkSequence')->with(null)->once()->andReturn(true);
        $connection->checkSequence(null);
        $this->assertEquals(false, $connection->dropSequence(null));
    }
}
