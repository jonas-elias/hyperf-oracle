<?php

namespace HyperfTest\Database\Oracle\Tests\Database;

use Hyperf\Database\Connection;
use Hyperf\Database\Oracle\Schema\Sequence;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class SequenceTest extends TestCase
{
    public function tearDown(): void
    {
        m::close();
    }

    public function testItWillCreateSequence()
    {
        $connection = $this->getConnection();
        $sequence = new Sequence($connection);
        $connection->shouldReceive('getConfig')->andReturn('sequence_config');
        $connection->shouldReceive('statement')->andReturn(true);

        $success = $sequence->create('users_id_seq');
        $this->assertTrue($success);
    }

    public function testItCanWrapSequenceNameWithSchemaPrefix()
    {
        $connection = $this->getConnection();
        $connection->shouldReceive('getConfig')->andReturn('schema_prefix');

        $sequence = new Sequence($connection);
        $name = $sequence->wrap('users_id_seq');
        $this->assertEquals('schema_prefix.users_id_seq', $name);
    }

    /** @test */
    public function testItWillDropSequence()
    {
        $sequence = m::mock(Sequence::class);
        $sequence->shouldReceive('drop')->with('users_id_seq')->andReturn(true);
        $sequence->shouldReceive('exists')->with('users_id_seq')->andReturn(false);

        $success = $sequence->drop('users_id_seq');
        $this->assertTrue($success);
    }

    protected function getConnection()
    {
        return m::mock(Connection::class);
    }
}
