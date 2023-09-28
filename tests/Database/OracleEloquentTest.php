<?php

namespace HyperfTest\Database\Oracle\Tests\Database;

use Mockery as m;
use PHPUnit\Framework\TestCase;

class OracleEloquentTest extends TestCase
{
    /**
     * {@inheritdoc}
     */
    public function tearDown(): void
    {
        m::close();
    }
}
