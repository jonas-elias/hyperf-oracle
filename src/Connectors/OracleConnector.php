<?php

declare(strict_types=1);

namespace Hyperf\Database\Oracle\Connectors;

use Exception;
use Hyperf\Database\Connectors\Connector;
use Hyperf\Database\Connectors\ConnectorInterface;
use PDO;
use PDOException;

/**
 * class OracleConnector
 *
 * @author <jonas-elias\> 
 */
class OracleConnector extends Connector implements ConnectorInterface
{
    /**
     * The default PDO connection options.
     *
     * @var array
     */
    protected $options = [
        PDO::ATTR_CASE => PDO::CASE_NATURAL,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_ORACLE_NULLS => PDO::NULL_NATURAL,
        PDO::ATTR_STRINGIFY_FETCHES => false,
    ];

    /**
     * Establish a database connection.
     *
     * @return PDO
     */
    public function connect(array $config)
    {
    }
}