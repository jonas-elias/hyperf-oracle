<?php

declare(strict_types=1);

namespace Hyperf\Database\Oracle\Query\Processors;

use DateTime;
use Hyperf\Database\Query\Builder;
use Hyperf\Database\Query\Processors\Processor;
use PDO;

class OracleProcessor extends Processor
{
    /**
     * Process an "insert get ID" query.
     *
     * @param string $sql
     * @param array $values
     * @param null|string $sequence
     * @return int
     */
    public function processInsertGetId(Builder $query, $sql, $values, $sequence = null)
    {
        $connection = $query->getConnection();

        $connection->recordsHaveBeenModified();
        $start = microtime(true);

        $id = 0;
        $parameter = 1;
        $statement = $this->prepareStatement($query, $sql);
        $values = $this->incrementBySequence($values, $sequence);
        $parameter = $this->bindValues($values, $statement, $parameter);
        $statement->bindParam($parameter, $id, PDO::PARAM_INT, -1);
        $statement->execute();

        $connection->logQuery((string) $sql, (array) $values, (float) $start);

        return (int) $id;
    }

    /**
     * Get prepared statement.
     *
     * @param  Builder  $query
     * @param  string  $sql
     * @return \PDOStatement
     */
    private function prepareStatement(Builder $query, $sql)
    {
        $connection = $query->getConnection();
        $pdo = $connection->getPdo();

        return $pdo->prepare($sql);
    }

    /**
     * Insert a new record and get the value of the primary key.
     *
     * @param  array  $values
     * @param  string  $sequence
     * @return array
     */
    protected function incrementBySequence(array $values, $sequence)
    {
        $builder = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 5)[3]['object'] ?? null;
        $builderArgs = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 5)[2]['args'] ?? null;

        if (! isset($builderArgs[1][0][$sequence])) {
            if ($builder) {
                $model = $builder->getModel();

                $connection = $model->getConnection();
                if ($model->sequence && $model->incrementing) {
                    $values[] = (int) $connection->getSequence()->nextValue($model->sequence);
                }
            }
        }

        return $values;
    }

    /**
     * Bind values to PDO statement.
     *
     * @param  array  $values
     * @param  \PDOStatement  $statement
     * @param  int  $parameter
     * @return int
     */
    private function bindValues(&$values, $statement, $parameter)
    {
        $count = count($values);
        for ($i = 0; $i < $count; $i++) {
            if (is_object($values[$i])) {
                if ($values[$i] instanceof DateTime) {
                    $values[$i] = $values[$i]->format('Y-m-d H:i:s');
                } else {
                    $values[$i] = (string) $values[$i];
                }
            }
            $type = $this->getPdoType($values[$i]);
            $statement->bindParam($parameter, $values[$i], $type);
            $parameter++;
        }

        return $parameter;
    }

    /**
     * Get PDO Type depending on value.
     *
     * @param  mixed  $value
     * @return int
     */
    private function getPdoType($value)
    {
        if (is_int($value)) {
            return PDO::PARAM_INT;
        }

        if (is_bool($value)) {
            return PDO::PARAM_BOOL;
        }

        if (is_null($value)) {
            return PDO::PARAM_NULL;
        }

        return PDO::PARAM_STR;
    }

    /**
     * Save Query with Blob returning primary key value.
     *
     * @param  Builder  $query
     * @param  string  $sql
     * @param  array  $values
     * @param  array  $binaries
     * @return int
     */
    public function saveLob(Builder $query, $sql, array $values, array $binaries)
    {
        $connection = $query->getConnection();

        $connection->recordsHaveBeenModified();
        $start = microtime(true);

        $id = 0;
        $parameter = 1;
        $statement = $this->prepareStatement($query, $sql);

        $parameter = $this->bindValues($values, $statement, $parameter);

        $countBinary = count($binaries);
        for ($i = 0; $i < $countBinary; $i++) {
            $statement->bindParam($parameter, $binaries[$i], PDO::PARAM_LOB, -1);
            $parameter++;
        }

        // bind output param for the returning clause.
        $statement->bindParam($parameter, $id, PDO::PARAM_INT, -1);

        if (! $statement->execute()) {
            return false;
        }

        $connection->logQuery($sql, $values, $start);

        return (int) $id;
    }

    /**
     * Process the results of a column listing query.
     *
     */
    public function processColumnListing(array $results): array
    {
        $mapping = function ($r) {
            $r = (object) $r;

            return strtolower($r->column_name);
        };

        return array_map($mapping, $results);
    }
}
