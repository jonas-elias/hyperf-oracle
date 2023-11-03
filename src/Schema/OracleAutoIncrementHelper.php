<?php

declare(strict_types=1);

namespace Hyperf\Database\Oracle\Schema;

use Hyperf\Database\ConnectionInterface;
use Hyperf\Database\Schema\Blueprint;

class OracleAutoIncrementHelper
{
    /**
     * @var ConnectionInterface
     */
    protected ConnectionInterface $connection;

    /**
     * @var Trigger
     */
    protected Trigger $trigger;

    /**
     * @var Sequence
     */
    protected Sequence $sequence;

    /**
     * Method constructor.
     *
     * @param ConnectionInterface $connection
     */
    public function __construct(ConnectionInterface $connection)
    {
        $this->connection = $connection;
        $this->sequence = new Sequence($connection);
        $this->trigger = new Trigger($connection);
    }

    /**
     * Create sequence and trigger for autoIncrement support.
     *
     * @param \Hyperf\Database\Schema\Blueprint $blueprint
     * @param string $table
     *
     * @return null
     */
    public function createAutoIncrementObjects(Blueprint $blueprint, $table): void
    {
        $column = $this->getQualifiedAutoIncrementColumn($blueprint);

        if (! is_null($column)) {
            $col = $column->name;
            $start = $column->start ?? $column->startingValue ?? 1;
    
            $prefix = $this->connection->getTablePrefix();
    
            $sequenceName = $this->createObjectName($prefix, $table, $col, 'seq');
            $this->sequence->create($sequenceName, $start, $column->nocache);
    
            $triggerName = $this->createObjectName($prefix, $table, $col, 'trg');
            $this->trigger->autoIncrement($prefix . $table, $col, $triggerName, $sequenceName);
        }
    }

    /**
     * Get qualified autoincrement column.
     *
     * @param Blueprint $blueprint
     *
     * @return \Hyperf\Support\Fluent|null
     */
    public function getQualifiedAutoIncrementColumn(Blueprint $blueprint): \Hyperf\Support\Fluent|null
    {
        $columns = $blueprint->getColumns();

        foreach ($columns as $column) {
            if ($column->autoIncrement) {
                return $column;
            }
        }

        return null;
    }

    /**
     * Drop sequence and triggers if exists, autoincrement objects.
     *
     * @param string $table
     *
     * @return void
     */
    public function dropAutoIncrementObjects(string $table): void
    {
        $prefix = $this->connection->getTablePrefix();
        $col = $this->getPrimaryKey($prefix . $table);

        if (isset($col) && ! empty($col)) {
            $sequenceName = $this->createObjectName($prefix, $table, $col, 'seq');
            $this->sequence->drop($sequenceName);

            $triggerName = $this->createObjectName($prefix, $table, $col, 'trg');
            $this->trigger->drop($triggerName);
        }
    }

    /**
     * Get table's primary key.
     *
     * @param string $table
     *
     * @return string
     */
    public function getPrimaryKey(string $table): string
    {
        if (! $table) {
            return '';
        }

        $sql = "SELECT cols.column_name
            FROM all_constraints cons, all_cons_columns cols
            WHERE upper(cols.table_name) = upper('{$table}')
                AND cons.constraint_type = 'P'
                AND cons.constraint_name = cols.constraint_name
                AND cons.owner = cols.owner
                AND cols.position = 1
                AND cons.owner = (select user from dual)
            ORDER BY cols.table_name, cols.position";
        $data = $this->connection->selectOne($sql);

        if ($data) {
            return $data->column_name;
        }

        return '';
    }

    /**
     * Get sequence instance.
     *
     * @return Sequence
     */
    public function getSequence(): Sequence
    {
        return $this->sequence;
    }

    /**
     * Set sequence instance.
     *
     * @param Sequence $sequence
     *
     * @return void
     */
    public function setSequence($sequence): void
    {
        $this->sequence = $sequence;
    }

    /**
     * Get trigger instance.
     *
     * @return Trigger
     */
    public function getTrigger(): Trigger
    {
        return $this->trigger;
    }

    /**
     * Set the trigger instance.
     *
     * @param Trigger $trigger
     *
     * @return void
     */
    public function setTrigger(Trigger $trigger): void
    {
        $this->trigger = $trigger;
    }

    /**
     * Create an object name that limits to 30 or 128 chars depending on the server version.
     *
     * @param string $prefix
     * @param string $table
     * @param string $col
     * @param string $type
     *
     * @return string
     */
    private function createObjectName(string $prefix, string $table, string $col, string $type): string
    {
        $maxLength = $this->connection->getSchemaGrammar()->getMaxLength();

        return substr($prefix . $table . '_' . $col . '_' . $type, 0, $maxLength);
    }
}
