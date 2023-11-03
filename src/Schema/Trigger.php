<?php

declare(strict_types=1);

namespace Hyperf\Database\Oracle\Schema;

class Trigger
{
    protected $connection;

    public function __construct($connection)
    {
        $this->connection = $connection;
    }

    /**
     * Function to create auto increment trigger for a table.
     *
     * @param string $table
     * @param string $column
     * @param string $triggerName
     * @param string $sequenceName
     *
     * @return bool
     */
    public function autoIncrement($table, $column, $triggerName, $sequenceName)
    {
        if (! ($table && $column && $triggerName && $sequenceName)) {
            return false;
        }

        $prefixSchema = $this->connection->getConfig('prefix_schema');
        if ($prefixSchema) {
            $table = $prefixSchema . '.' . $table;
            $triggerName = $prefixSchema . '.' . $triggerName;
            $sequenceName = $prefixSchema . '.' . $sequenceName;
        }

        $table = $this->wrapValue($table);
        $column = $this->wrapValue($column);

        return $this->connection->statement("
            create trigger $triggerName
            before insert on {$table}
            for each row
                begin
            if :new.{$column} is null then
                select {$sequenceName}.nextval into :new.{$column} from dual;
            end if;
            end;");
    }

    /**
     * Function to safely drop trigger db object.
     *
     * @param string $name
     *
     * @return bool
     */
    public function drop($name)
    {
        if (! $name) {
            return false;
        }

        return $this->connection->statement("declare
                e exception;
                pragma exception_init(e,-4080);
            begin
                execute immediate 'drop trigger {$name}';
            exception
            when e then
                null;
            end;");
    }

    /**
     * Wrap value if reserved word.
     *
     * @param string $value
     *
     * @return string
     */
    protected function wrapValue($value)
    {
        $value = strtoupper($value);

        return $value;
    }
}
