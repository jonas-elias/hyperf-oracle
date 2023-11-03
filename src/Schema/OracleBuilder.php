<?php

declare(strict_types=1);

namespace Hyperf\Database\Oracle\Schema;

use Closure;
use Hyperf\Database\Connection;
use Hyperf\Database\PgSQL\Query\Processors\PostgresProcessor;
use Hyperf\Database\Schema\Builder;

use function Hyperf\Collection\head;

class OracleBuilder extends Builder
{
    /**
     * @var OracleAutoIncrementHelper
     */
    protected OracleAutoIncrementHelper $helper;

    /**
     * @var OraclePreferences
     */
    protected $ctxPreferences;

    /**
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        parent::__construct($connection);
        $this->ctxPreferences = new OraclePreferences($connection);
        $this->helper = new OracleAutoIncrementHelper($connection);
    }

    /**
     * Create a new table on the schema.
     *
     * @param string $table
     */
    public function create($table, Closure $callback): void
    {
        $blueprint = $this->createBlueprint($table);

        $blueprint->create();

        $callback($blueprint);

        $this->ctxPreferences->createPreferences($blueprint);

        $this->build($blueprint);

        $this->helper->createAutoIncrementObjects($blueprint, $table);
    }

    /**
     * Create a database in the schema.
     *
     * @param string $name
     *
     * @return bool
     */
    public function createDatabase(string $name): bool
    {
        return false;
    }

    /**
     * Indicate that the table should be dropped if it exists.
     *
     * @param string $table
     *
     * @return void
     */
    public function dropIfExists($table): void
    {
        $this->helper->dropAutoIncrementObjects($table);
        $this->ctxPreferences->dropPreferencesByTable($table);
        parent::dropIfExists($table);
    }

    /**
     * Drop a database from the schema if the database exists.
     *
     * @param string $name
     */
    public function dropDatabaseIfExists($name): bool
    {
        return false;
    }

    /**
     * Determine if the given table exists.
     *
     * @param string $table
     */
    public function hasTable($table): bool
    {
        [$schema, $table] = $this->parseSchemaAndTable($table);

        $table = $this->connection->getTablePrefix() . $table;

        return count($this->connection->select(
            $this->grammar->compileTableExists(),
            [$schema, $table]
        )) > 0;
    }

    /**
     * Drop all tables from the database.
     */
    public function dropAllTables(): void
    {
        $tables = [];
        
        $this->ctxPreferences->dropAllPreferences();

        $this->connection->statement(
            $this->grammar->compileDropAllTables($tables)
        );
    }

    /**
     * Drop all views from the database.
     */
    public function dropAllViews(): void
    {
        $views = [];

        foreach ($this->getAllViews() as $row) {
            $row = (array) $row;

            $views[] = reset($row);
        }

        if (empty($views)) {
            return;
        }

        $this->connection->statement(
            $this->grammar->compileDropAllViews($views)
        );
    }

    /**
     * Drop all types from the database.
     */
    public function dropAllTypes()
    {
        $types = [];

        foreach ($this->getAllTypes() as $row) {
            $row = (array) $row;

            $types[] = reset($row);
        }

        if (empty($types)) {
            return;
        }

        $this->connection->statement(
            $this->grammar->compileDropAllTypes($types),
        );
    }

    /**
     * Get all of the table names for the database.
     */
    public function getAllTables(): array
    {
        return $this->connection->select(
            $this->grammar->compileGetAllTables((array) $this->connection->getConfig('schema'))
        );
    }

    /**
     * Get all of the view names for the database.
     */
    public function getAllViews(): array
    {
        return [];
        // return $this->connection->select(
        //     $this->grammar->compileGetAllViews((array) $this->connection->getConfig('schema'))
        // );
    }

    /**
     * Get all of the type names for the database.
     *
     * @return array
     */
    public function getAllTypes()
    {
        return [];
        // return $this->connection->select(
        //     $this->grammar->compileGetAllTypes()
        // );
    }

    /**
     * Get the column listing for a given table.
     *
     * @param string $table
     */
    public function getColumnListing($table): array
    {
        [$schema, $table] = $this->parseSchemaAndTable($table);

        $databaseName = $this->connection->getDatabaseName();

        $table = $this->connection->getTablePrefix() . $table;

        $results = $this->connection->select(
            $this->grammar->compileColumnListing(),
            [$databaseName, $schema, $table]
        );

        return $this->connection->getPostProcessor()->processColumnListing($results);
    }

    /**
     * Get the column type listing for a given table.
     *
     * @param string $table
     *
     * @return array
     */
    public function getColumnTypeListing($table)
    {
        [$schema, $table] = $this->parseSchemaAndTable($table);

        $table = $this->connection->getTablePrefix() . $table;

        $results = $this->connection->select(
            $this->grammar->compileColumnListing(),
            [$this->connection->getDatabaseName(), $schema, $table]
        );

        /** @var PostgresProcessor $processor */
        $processor = $this->connection->getPostProcessor();
        return $processor->processListing($results);
    }

    protected function createBlueprint($table, Closure $callback = null)
    {
        $blueprint = new OracleBlueprint($table, $callback);
        $blueprint->setTablePrefix($this->connection->getTablePrefix());
        $blueprint->setMaxLength($this->grammar->getMaxLength());

        return $blueprint;
    }

    /**
     * Parse the table name and extract the schema and table.
     *
     * @param string $table
     *
     * @return array
     */
    protected function parseSchemaAndTable($table)
    {
        $table = explode('.', $table);

        if (is_array($schema = $this->connection->getConfig('schema'))) {
            if (in_array($table[0], $schema, true)) {
                return [array_shift($table), implode('.', $table)];
            }

            $schema = head($schema);
        }

        return [$schema ?: 'public', implode('.', $table)];
    }
}
