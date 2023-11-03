<?php

declare(strict_types=1);

namespace Hyperf\Database\Oracle\Schema;

use Hyperf\Database\ConnectionInterface;

class Sequence
{
    /**
     * @var ConnectionInterface
     */
    protected ConnectionInterface $connection;

    /**
     * Method constructor.
     *
     * @param ConnectionInterface $connection
     *
     * @return void
     */
    public function __construct(ConnectionInterface $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Function to create oracle sequence.
     *
     * @param string $name
     * @param int $start
     * @param bool $nocache
     * @param int $min
     * @param bool $max
     * @param int $increment
     *
     * @return bool
     */
    public function create($name, $start = 1, $nocache = false, $min = 1, $max = false, $increment = 1)
    {
        if (! $name) {
            return false;
        }

        $name = $this->wrap($name);

        $nocache = $nocache ? 'nocache' : '';

        $max = $max ? " maxvalue {$max}" : '';

        $sequence_stmt = "create sequence {$name} minvalue {$min} {$max} start with {$start} increment by {$increment} {$nocache}";

        return $this->connection->statement($sequence_stmt);
    }

    /**
     * Wrap sequence name with schema prefix.
     *
     * @param string $name
     *
     * @return string
     */
    public function wrap($name)
    {
        if ($this->connection->getConfig('prefix_schema')) {
            return $this->connection->getConfig('prefix_schema') . '.' . $name;
        }

        return $name;
    }

    /**
     * Function to safely drop sequence db object.
     *
     * @param string $name
     *
     * @return bool
     */
    public function drop($name)
    {
        if (! $name || ! $this->exists($name)) {
            return false;
        }

        $name = $this->wrap($name);

        return $this->connection->statement("
            declare
                e exception;
                pragma exception_init(e,-02289);
            begin
                execute immediate 'drop sequence {$name}';
            exception
            when e then
                null;
            end;");
    }

    /**
     * Function to check if sequence exists.
     *
     * @param string $name
     *
     * @return bool
     */
    public function exists($name)
    {
        if (! $name) {
            return false;
        }

        $name = $this->wrap($name);

        return $this->connection->selectOne(
            "select * from all_sequences where sequence_name=upper('{$name}') and sequence_owner=upper(user)"
        );
    }

    /**
     * Get sequence next value.
     *
     * @param string $name
     *
     * @return int
     */
    public function nextValue($name)
    {
        if (! $name) {
            return 0;
        }

        $name = $this->wrap($name);

        return $this->connection->selectOne("SELECT $name.NEXTVAL as \"id\" FROM DUAL")->id;
    }

    /**
     * Same function as lastInsertId. added for clarity with oracle sql statement.
     *
     * @param string $name
     *
     * @return int
     */
    public function currentValue($name)
    {
        return $this->lastInsertId($name);
    }

    /**
     * Function to get oracle sequence last inserted id.
     *
     * @param string $name
     *
     * @return int
     */
    public function lastInsertId($name)
    {
        if (! $name || ! $this->exists($name)) {
            return 0;
        }

        $name = $this->wrap($name);

        return $this->connection->selectOne("select {$name}.currval as \"id\" from dual")->id;
    }
}
