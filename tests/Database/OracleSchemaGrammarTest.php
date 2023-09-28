<?php

namespace HyperfTest\Database\Oracle\Tests\Database;

use Mockery as m;
use PHPUnit\Framework\TestCase;

class OracleSchemaGrammarTest extends TestCase
{
    public function tearDown(): void
    {
        m::close();
    }
}
