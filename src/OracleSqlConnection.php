<?php

declare(strict_types=1);

namespace Hyperf\Database\Oracle;

use Hyperf\Database\Connection;
use Hyperf\Database\Oracle\Query\Grammars\OracleGrammar as QueryGrammar;
use Hyperf\Database\Oracle\Schema\Grammars\OracleGrammar;
use Hyperf\Database\Oracle\Schema\OracleBuilder;
use PDOStatement;

class OracleSqlConnection extends Connection
{
    /**
     * Get a schema builder instance for the connection.
     */
    public function getSchemaBuilder(): OracleBuilder
    {
        if (is_null($this->schemaGrammar)) {
            $this->useDefaultSchemaGrammar();
        }

        return new OracleBuilder($this);
    }

    /**
     * Bind values to their parameters in the given statement.
     */
    public function bindValues(PDOStatement $statement, array $bindings): void
    {
        foreach ($bindings as $key => $value) {
            $statement->bindValue(
                is_string($key) ? $key : $key + 1,
                $value
            );
        }
    }

    /**
     * Get the default query grammar instance.
     *
     * @return \Hyperf\Database\Oracle\Schema\Grammars\OracleGrammar
     */
    protected function getDefaultQueryGrammar(): QueryGrammar
    {
        return $this->withTablePrefix(new QueryGrammar());
    }

    /**
     * Get the default schema grammar instance.
     */
    protected function getDefaultSchemaGrammar(): OracleGrammar
    {
        return $this->withTablePrefix(new OracleGrammar());
    }
}
