<?php

namespace HyperfTest\Database\Oracle\Tests\Database;

use Hyperf\Database\Connection;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class SequenceTest extends TestCase
{
    public function tearDown(): void
    {
        m::close();
    }
}
