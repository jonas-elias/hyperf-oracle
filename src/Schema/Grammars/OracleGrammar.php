<?php

declare(strict_types=1);

namespace Hyperf\Database\Oracle\Schema\Grammars;

use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Grammars\Grammar;
use Hyperf\Support\Fluent;
use Hyperf\Database\Connection;
use Hyperf\Database\Query\Expression;

use function Hyperf\Collection\collect;

class OracleGrammar extends Grammar
{
    /**
     * The keyword identifier wrapper format.
     *
     * @var string
     */
    protected string $wrapper = '%s';

    /**
     * The possible column modifiers.
     *
     * @var array
     */
    protected array $modifiers = ['Increment', 'Nullable', 'Default'];

    /**
     * The possible column serials.
     *
     * @var array
     */
    protected array $serials = ['bigInteger', 'integer', 'mediumInteger', 'smallInteger', 'tinyInteger'];

    /**
     * @var string
     */
    protected string $schemaPrefix = '';

    /**
     * @var int
     */
    protected int $maxLength = 50;

    /**
     * If this Grammar supports schema changes wrapped in a transaction.
     *
     * @var bool
     */
    protected bool $transactions = true;

    /**
     * Compile a create table command.
     *
     * @param \Hyperf\Database\Schema\Blueprint $blueprint
     * @param \Hyperf\Support\Fluent $command
     * @return string
     */
    public function compileCreate(Blueprint $blueprint, Fluent $command): string
    {
        $columns = implode(', ', $this->getColumns($blueprint));

        $sql = 'create table ' . $this->wrapTable($blueprint) . " ( $columns";

        /*
         * To be able to name the primary/foreign keys when the table is
         * initially created we will need to check for a primary/foreign
         * key commands and add the columns to the table's declaration
         * here so they can be created on the tables.
         */
        $sql .= (string) $this->addForeignKeys($blueprint);

        $sql .= (string) $this->addPrimaryKeys($blueprint);

        $sql .= ' )';

        return $sql;
    }

    /**
     * Get the primary key syntax for a table creation statement.
     *
     * @param \Hyperf\Database\Schema\Blueprint $blueprint
     * @return string
     */
    protected function addPrimaryKeys(Blueprint $blueprint): string
    {
        $primary = $this->getCommandByName($blueprint, 'primary');

        if (!is_null($primary)) {
            $columns = $this->columnize($primary->columns);

            return ", constraint {$primary->index} primary key ( {$columns} )";
        }

        return '';
    }

    /**
     * Compile the blueprint's column definitions.
     *
     * @param \Hyperf\Database\Schema\Blueprint $blueprint
     * @return array
     */
    protected function getColumns(Blueprint $blueprint): array
    {
        $columns = [];

        foreach ($blueprint->getAddedColumns() as $column) {
            // Each of the column types have their own compiler functions which are tasked
            // with turning the column definition into its SQL format for this platform
            // used by the connection. The column's modifiers are compiled and added.
            $sql = $this->wrap($column) . ' ' . $this->getType($column);

            $columns[] = $this->addModifiers($sql, $blueprint, $column);
        }

        return $columns;
    }

    /**
     * @param string $name
     * @param \Hyperf\Database\Connection $connection
     * @return bool
     */
    public function compileCreateDatabase(string $name, Connection $connection): bool
    {
        return true;
    }

    /**
     * Compile a drop fulltext index command.
     *
     * @param \Hyperf\Database\Schema\Blueprint $blueprint
     * @param \Hyperf\Support\Fluent $command
     * @return string
     */
    public function compileDropFullText(Blueprint $blueprint, Fluent $command): string
    {
        $columns = str_replace('"', "'", $this->columnize($command->columns));

        if (empty($columns)) {
            return $this->compileDropIndex($blueprint, $command);
        }

        $dropFullTextSql = "for idx_rec in (select idx_name from ctx_user_indexes where idx_text_name in ($columns)) loop
            execute immediate 'drop index ' || idx_rec.idx_name;
        end loop;";

        return "begin $dropFullTextSql end;";
    }

    /**
     * Compile a fulltext index key command.
     *
     * @param  \Hyperf\Database\Schema\Blueprint  $blueprint
     * @param  \Hyperf\Support\Fluent  $command
     * @return string
     */
    public function compileFullText(Blueprint $blueprint, Fluent $command): string
    {
        $tableName = $this->wrapTable($blueprint);
        $columns = $command->columns;
        $indexBaseName = $command->index;
        $preferenceName = $indexBaseName . '_preference';

        $sqlStatements = [];

        foreach ($columns as $key => $column) {
            $indexName = $indexBaseName;
            $parametersIndex = '';

            if (count($columns) > 1) {
                $indexName .= "_{$key}";
                $parametersIndex = "datastore {$preferenceName} ";
            }

            $parametersIndex .= 'sync(on commit)';

            $sql = "execute immediate 'create index {$indexName} on $tableName ($column) indextype is 
                ctxsys.context parameters (''$parametersIndex'')';";

            $sqlStatements[] = $sql;
        }

        return "begin " . implode(' ', $sqlStatements) . " end;";
    }

    /**
     * Wrap a table in keyword identifiers.
     *
     * @param  mixed  $table
     * @return string
     */
    public function wrapTable(mixed $table): string
    {
        return str_replace('"', '', parent::wrapTable(
            $table instanceof Blueprint ? $table->getTable() : $table
        ));
    }

    /**
     * Wrap a value in keyword identifiers.
     *
     * @param  Fluent|Expression|string  $value
     * @param  bool  $prefixAlias
     * @return string
     */
    public function wrap(Fluent|Expression|string $value, $prefixAlias = false): string
    {
        return parent::wrap(
            $value instanceof Fluent ? strtoupper($value->name) : strtoupper($value), $prefixAlias
        );
    }

    /**
     * Get the schema prefix.
     *
     * @return string
     */
    public function getSchemaPrefix(): string
    {
        return !empty($this->schemaPrefix) ? $this->schemaPrefix . '.' : '';
    }

    /**
     * Get max length.
     *
     * @return int
     */
    public function getMaxLength(): int
    {
        return !empty($this->maxLength) ? $this->maxLength : 30;
    }

    /**
     * Set the schema prefix.
     *
     * @param  string  $prefix
     * @return void
     */
    public function setSchemaPrefix(string $prefix): void
    {
        $this->schemaPrefix = $prefix;
    }

    /**
     * Set max length.
     *
     * @param  int  $length
     * @return void
     */
    public function setMaxLength(int $length): void
    {
        $this->maxLength = $length;
    }

    /**
     * Get the foreign key syntax for a table creation statement.
     *
     * @param \Hyperf\Database\Schema\Blueprint $blueprint
     * @return string
     */
    protected function addForeignKeys(Blueprint $blueprint): string
    {
        $sql = '';

        $foreigns = $this->getCommandsByName($blueprint, 'foreign');

        // Once we have all the foreign key commands for the table creation statement
        // we'll loop through each of them and add them to the create table SQL we
        // are building
        foreach ($foreigns as $foreign) {
            $on = $this->wrapTable($foreign->on);

            $columns = $this->columnize($foreign->columns);

            $onColumns = $this->columnize((array) $foreign->references);

            $sql .= ", constraint {$foreign->index} foreign key ( {$columns} ) references {$on} ( {$onColumns} )";

            // Once we have the basic foreign key creation statement constructed we can
            // build out the syntax for what should happen on an update or delete of
            // the affected columns, which will get something like "cascade", etc.
            if (!is_null($foreign->onDelete)) {
                $sql .= " on delete {$foreign->onDelete}";
            }
        }

        return $sql;
    }

    /**
     * Compile the query to determine if a table exists.
     *
     * @return string
     */
    public function compileTableExists(): string
    {
        return "select * from all_tables where upper(owner) = upper(?) and upper(table_name) = upper(?)";
    }

    /**
     * @param \Hyperf\Database\Schema\Blueprint $blueprint
     * @return array
     */
    public function compileAutoIncrementStartingValues(Blueprint $blueprint): array
    {
        return collect($blueprint->autoIncrementingStartingValues())->map(function ($value, $column) use ($blueprint) {
            return 'alter sequence ' . $blueprint->getTable() . '_' . $column . '_seq restart with ' . $value;
        })->all();
    }

    /**
     * Compile the query to determine the list of columns.
     *
     * @param  string  $database
     * @param  string  $table
     * @return string
     */
    public function compileColumnExists(string $database, string $table): string
    {
        return "select column_name from all_tab_cols where upper(owner) = upper('{$database}') and upper(table_name) = upper('{$table}')";
    }

    /**
     * Compile an add column command.
     *
     * @param \Hyperf\Database\Schema\Blueprint $blueprint
     * @param \Hyperf\Support\Fluent $command
     * @return string
     */
    public function compileAdd(Blueprint $blueprint, Fluent $command): string
    {
        $columns = implode(', ', $this->getColumns($blueprint));

        $sql = 'alter table ' . $this->wrapTable($blueprint) . " add ( $columns";

        $sql .= (string) $this->addPrimaryKeys($blueprint);

        return $sql .= ' )';
    }

    /**
     * Compile a primary key command.
     *
     * @param \Hyperf\Database\Schema\Blueprint $blueprint
     * @param \Hyperf\Support\Fluent $command
     * @return string
     */
    public function compilePrimary(Blueprint $blueprint, Fluent $command): string
    {
        $create = $this->getCommandByName($blueprint, 'create');

        if (is_null($create)) {
            $columns = $this->columnize($command->columns);

            $table = $this->wrapTable($blueprint);

            return "alter table {$table} add constraint {$command->index} primary key ({$columns})";
        }

        return '';
    }

    /**
     * Compile a foreign key command.
     *
     * @param \Hyperf\Database\Schema\Blueprint $blueprint
     * @param \Hyperf\Support\Fluent $command
     * @return string
     */
    public function compileForeign(Blueprint $blueprint, Fluent $command): string
    {
        $create = $this->getCommandByName($blueprint, 'create');

        if (is_null($create)) {
            $table = $this->wrapTable($blueprint);

            $on = $this->wrapTable($command->on);

            // We need to prepare several of the elements of the foreign key definition
            // before we can create the SQL, such as wrapping the tables and convert
            // an array of columns to comma-delimited strings for the SQL queries.
            $columns = $this->columnize($command->columns);

            $onColumns = $this->columnize((array) $command->references);

            $sql = "alter table {$table} add constraint {$command->index} ";

            $sql .= "foreign key ( {$columns} ) references {$on} ( {$onColumns} )";

            // Once we have the basic foreign key creation statement constructed we can
            // build out the syntax for what should happen on an update or delete of
            // the affected columns, which will get something like "cascade", etc.
            if (!is_null($command->onDelete)) {
                $sql .= " on delete {$command->onDelete}";
            }

            return $sql;
        }

        return '';
    }

    /**
     * Compile a unique key command.
     *
     * @param  \Hyperf\Database\Schema\Blueprint  $blueprint
     * @param  \Hyperf\Support\Fluent  $command
     * @return string
     */
    public function compileUnique(Blueprint $blueprint, Fluent $command): string
    {
        return 'alter table ' . $this->wrapTable($blueprint) . " add constraint {$command->index} unique ( " . $this->columnize($command->columns) . ' )';
    }

    /**
     * Compile a plain index key command.
     *
     * @param  \Hyperf\Database\Schema\Blueprint  $blueprint
     * @param  \Hyperf\Support\Fluent  $command
     * @return string
     */
    public function compileIndex(Blueprint $blueprint, Fluent $command): string
    {
        return "create index {$command->index} on " . $this->wrapTable($blueprint) . ' ( ' . $this->columnize($command->columns) . ' )';
    }

    /**
     * Compile a drop table command.
     *
     * @param  \Hyperf\Database\Schema\Blueprint  $blueprint
     * @param  \Hyperf\Support\Fluent  $command
     * @return string
     */
    public function compileDrop(Blueprint $blueprint, Fluent $command): string
    {
        return 'drop table ' . $this->wrapTable($blueprint);
    }

    /**
     * Compile the SQL needed to drop all tables.
     *
     * @return string
     */
    public function compileDropAllTables(): string
    {
        $compiledStatements = '';

        $compiledStatements .= 'BEGIN';

        // Drop all tables
        $compiledStatements .= '
        FOR c IN (SELECT table_name FROM user_tables WHERE secondary = \'N\') LOOP
            EXECUTE IMMEDIATE (\'DROP TABLE "\' || c.table_name || \'" CASCADE CONSTRAINTS\');
        END LOOP;';

        // Drop all sequences
        $compiledStatements .= '
        FOR s IN (SELECT sequence_name FROM user_sequences) LOOP
            EXECUTE IMMEDIATE (\'DROP SEQUENCE \' || s.sequence_name);
        END LOOP;';

        $compiledStatements .= 'END;';

        return $compiledStatements;
    }

    /**
     * Compile a drop table (if exists) command.
     *
     * @param  \Hyperf\Database\Schema\Blueprint  $blueprint
     * @param  \Hyperf\Support\Fluent  $command
     * @return string
     */
    public function compileDropIfExists(Blueprint $blueprint, Fluent $command): string
    {
        $table = $this->wrapTable($blueprint);

        return "declare c int;
            begin
               select count(*) into c from user_tables where table_name = upper('$table');
                  execute immediate 'drop table $table';
            end;";
    }

    /**
     * Compile a drop column command.
     *
     * @param  \Hyperf\Database\Schema\Blueprint  $blueprint
     * @param  \Hyperf\Support\Fluent  $command
     * @return string
     */
    public function compileDropColumn(Blueprint $blueprint, Fluent $command)
    {
        $columns = $this->wrapArray($command->columns);

        $table = $this->wrapTable($blueprint);

        return 'alter table ' . $table . ' drop ( ' . implode(', ', $columns) . ' )';
    }

    /**
     * Compile a drop primary key command.
     *
     * @return string
     */
    public function compileDropPrimary(Blueprint $blueprint, Fluent $command)
    {
        return $this->dropConstraint($blueprint, $command, 'primary');
    }

    /**
     * @param  Blueprint  $blueprint
     * @param  Fluent  $command
     * @param  string  $type
     * @return string
     */
    private function dropConstraint(Blueprint $blueprint, Fluent $command, $type)
    {
        $table = $this->wrapTable($blueprint);

        $index = substr($command->index, 0, $this->getMaxLength());

        if ($type === 'index') {
            return "drop index {$index}";
        }

        return "alter table {$table} drop constraint {$index}";
    }

    /**
     * Compile a drop unique key command.
     *
     * @return string
     */
    public function compileDropUnique(Blueprint $blueprint, Fluent $command)
    {
        return $this->dropConstraint($blueprint, $command, 'unique');
    }

    /**
     * Compile a drop index command.
     *
     * @return string
     */
    public function compileDropIndex(Blueprint $blueprint, Fluent $command)
    {
        return $this->dropConstraint($blueprint, $command, 'index');
    }

    /**
     * Compile a drop foreign key command.
     *
     * @return string
     */
    public function compileDropForeign(Blueprint $blueprint, Fluent $command)
    {
        return $this->dropConstraint($blueprint, $command, 'foreign');
    }

    /**
     * Compile a rename table command.
     *
     * @param  \Hyperf\Database\Schema\Blueprint  $blueprint
     * @param  \Hyperf\Support\Fluent  $command
     * @return string
     */
    public function compileRename(Blueprint $blueprint, Fluent $command)
    {
        $from = $this->wrapTable($blueprint);

        return "alter table {$from} rename to " . $this->wrapTable($command->to);
    }

    /**
     * Create the column definition for a char type.
     *
     * @param  \Hyperf\Support\Fluent  $column
     * @return string
     */
    protected function typeChar(Fluent $column)
    {
        return "char({$column->length})";
    }

    /**
     * Create the column definition for a string type.
     *
     * @param  \Hyperf\Support\Fluent  $column
     * @return string
     */
    protected function typeString(Fluent $column)
    {
        return "varchar2({$column->length})";
    }

    /**
     * Create column definition for a nvarchar type.
     *
     * @param  \Hyperf\Support\Fluent  $column
     * @return string
     */
    protected function typeNvarchar2(Fluent $column)
    {
        return "nvarchar2({$column->length})";
    }

    /**
     * Create the column definition for a text type.
     *
     * @param  \Hyperf\Support\Fluent  $column
     * @return string
     */
    protected function typeText(Fluent $column)
    {
        return 'clob';
    }

    /**
     * Create the column definition for a medium text type.
     *
     * @param  \Hyperf\Support\Fluent  $column
     * @return string
     */
    protected function typeMediumText(Fluent $column)
    {
        return 'clob';
    }

    /**
     * Create the column definition for a long text type.
     *
     * @param  \Hyperf\Support\Fluent  $column
     * @return string
     */
    protected function typeLongText(Fluent $column)
    {
        return 'clob';
    }

    /**
     * Create the column definition for a integer type.
     *
     * @param  \Hyperf\Support\Fluent  $column
     * @return string
     */
    protected function typeInteger(Fluent $column)
    {
        $length = ($column->length) ? $column->length : 10;

        return "number({$length},0)";
    }

    /**
     * Create the column definition for a integer type.
     *
     * @param  \Hyperf\Support\Fluent  $column
     * @return string
     */
    protected function typeBigInteger(Fluent $column)
    {
        $length = ($column->length) ? $column->length : 19;

        return "number({$length},0)";
    }

    /**
     * Create the column definition for a medium integer type.
     *
     * @param  \Hyperf\Support\Fluent  $column
     * @return string
     */
    protected function typeMediumInteger(Fluent $column)
    {
        $length = ($column->length) ? $column->length : 7;

        return "number({$length},0)";
    }

    /**
     * Create the column definition for a small integer type.
     *
     * @param  \Hyperf\Support\Fluent  $column
     * @return string
     */
    protected function typeSmallInteger(Fluent $column)
    {
        $length = ($column->length) ? $column->length : 5;

        return "number({$length},0)";
    }

    /**
     * Create the column definition for a tiny integer type.
     *
     * @param  \Hyperf\Support\Fluent  $column
     * @return string
     */
    protected function typeTinyInteger(Fluent $column)
    {
        $length = ($column->length) ? $column->length : 3;

        return "number({$length},0)";
    }

    /**
     * Create the column definition for a float type.
     *
     * @param  \Hyperf\Support\Fluent  $column
     * @return string
     */
    protected function typeFloat(Fluent $column)
    {
        return "number({$column->total}, {$column->places})";
    }

    /**
     * Create the column definition for a double type.
     *
     * @param  \Hyperf\Support\Fluent  $column
     * @return string
     */
    protected function typeDouble(Fluent $column)
    {
        return "number({$column->total}, {$column->places})";
    }

    /**
     * Create the column definition for a decimal type.
     *
     * @param  \Hyperf\Support\Fluent  $column
     * @return string
     */
    protected function typeDecimal(Fluent $column)
    {
        return "number({$column->total}, {$column->places})";
    }

    /**
     * Create the column definition for a boolean type.
     *
     * @param  \Hyperf\Support\Fluent  $column
     * @return string
     */
    protected function typeBoolean(Fluent $column)
    {
        return 'char(1)';
    }

    /**
     * Create the column definition for a enum type.
     *
     * @param  \Hyperf\Support\Fluent  $column
     * @return string
     */
    protected function typeEnum(Fluent $column)
    {
        $length = ($column->length) ? $column->length : 255;

        return "varchar2({$length})";
    }

    /**
     * Create the column definition for a date type.
     *
     * @param  \Hyperf\Support\Fluent  $column
     * @return string
     */
    protected function typeDate(Fluent $column)
    {
        return 'date';
    }

    /**
     * Create the column definition for a date-time type.
     *
     * @param  \Hyperf\Support\Fluent  $column
     * @return string
     */
    protected function typeDateTime(Fluent $column)
    {
        return 'date';
    }

    /**
     * Create the column definition for a time type.
     *
     * @param  \Hyperf\Support\Fluent  $column
     * @return string
     */
    protected function typeTime(Fluent $column)
    {
        return 'date';
    }

    /**
     * Create the column definition for a timestamp type.
     *
     * @param  \Hyperf\Support\Fluent  $column
     * @return string
     */
    protected function typeTimestamp(Fluent $column)
    {
        return 'timestamp';
    }

    /**
     * Create the column definition for a timestamp type with timezone.
     *
     * @param  Fluent  $column
     * @return string
     */
    protected function typeTimestampTz(Fluent $column)
    {
        return 'timestamp with time zone';
    }

    /**
     * Create the column definition for a binary type.
     *
     * @param  \Hyperf\Support\Fluent  $column
     * @return string
     */
    protected function typeBinary(Fluent $column)
    {
        return 'blob';
    }

    /**
     * Create the column definition for a uuid type.
     *
     * @param  \Hyperf\Support\Fluent  $column
     * @return string
     */
    protected function typeUuid(Fluent $column)
    {
        return 'char(36)';
    }

    /**
     * Create the column definition for an IP address type.
     *
     * @param  \Hyperf\Support\Fluent  $column
     * @return string
     */
    protected function typeIpAddress(Fluent $column)
    {
        return 'varchar(45)';
    }

    /**
     * Create the column definition for a MAC address type.
     *
     * @param  \Hyperf\Support\Fluent  $column
     * @return string
     */
    protected function typeMacAddress(Fluent $column)
    {
        return 'varchar(17)';
    }

    /**
     * Create the column definition for a json type.
     *
     * @param  \Hyperf\Support\Fluent  $column
     * @return string
     */
    protected function typeJson(Fluent $column)
    {
        return 'clob';
    }

    /**
     * Create the column definition for a jsonb type.
     *
     * @param  \Hyperf\Support\Fluent  $column
     * @return string
     */
    protected function typeJsonb(Fluent $column)
    {
        return 'clob';
    }

    /**
     * Get the SQL for a nullable column modifier.
     *
     * @param  \Hyperf\Database\Schema\Blueprint  $blueprint
     * @param  \Hyperf\Support\Fluent  $column
     * @return string
     */
    protected function modifyNullable(Blueprint $blueprint, Fluent $column)
    {
        // check if field is declared as enum
        $enum = '';
        if (count((array) $column->allowed)) {
            $columnName = $this->wrapValue($column->name);
            $enum = " check ({$columnName} in ('" . implode("', '", $column->allowed) . "'))";
        }

        $null = $column->nullable ? ' null' : ' not null';
        $null .= $enum;

        if (!is_null($column->default)) {
            return ' default ' . $this->getDefaultValue($column->default) . $null;
        }

        return $null;
    }

    /**
     * Get the SQL for a default column modifier.
     *
     * @param  \Hyperf\Database\Schema\Blueprint  $blueprint
     * @param  \Hyperf\Support\Fluent  $column
     * @return string
     */
    protected function modifyDefault(Blueprint $blueprint, Fluent $column)
    {
        // implemented @modifyNullable
        return '';
    }

    /**
     * Get the SQL for an auto-increment column modifier.
     *
     * @param  \Hyperf\Database\Schema\Blueprint  $blueprint
     * @param  \Hyperf\Support\Fluent  $column
     * @return string|null
     */
    protected function modifyIncrement(Blueprint $blueprint, Fluent $column)
    {
        if (in_array($column->type, $this->serials) && $column->autoIncrement) {
            $blueprint->primary($column->name);
        }
    }

    /**
     * Wrap a single string in keyword identifiers.
     *
     * @param  string  $value
     * @return string
     */
    // protected function wrapValue($value)
    // {
    //     if ($this->isReserved($value)) {
    //         return strtoupper(parent::wrapValue($value));
    //     }

    //     return $value !== '*' ? sprintf($this->wrapper, $value) : $value;
    // }
}