<?php

declare(strict_types=1);

namespace Hyperf\Database\Oracle\Connectors;

use Exception;
use Hyperf\Database\Connectors\Connector;
use Hyperf\Database\Connectors\ConnectorInterface;
use PDO;

/**
 * class OracleConnector
 *
 * @author jonas-elias
 * @extends OracleConnector
 * @implements ConnectorInterface
 */
class OracleConnector extends Connector implements ConnectorInterface
{
    /**
     * The default PDO options oracle.
     *
     * @var array
     */
    protected $options = [
        PDO::ATTR_CASE => PDO::CASE_LOWER,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_ORACLE_NULLS => PDO::NULL_NATURAL,
        PDO::ATTR_STRINGIFY_FETCHES => false,
        PDO::ATTR_AUTOCOMMIT => false,
    ];

    /**
     * Establish a database connection.
     *
     * @param array $config
     * @return PDO
     */
    public function connect(array $config): PDO
    {
        // configure auto commit application with DB_AUTO_COMMIT.
        $this->configureAutoCommit($config);

        // create connection and verify lost connection.
        $connection = $this->createConnection(
            $this->getTNS($config),
            $config,
            $this->getOptions($config)
        );

        // configure timezone in session connection.
        $this->configureTimezone($connection, $config);

        // configure date format in session connection.
        $this->configureDateFormat($connection);

        return $connection;
    }

    /**
     * Create a new PDO connection.
     *
     * @param string $dsn
     * @return PDO
     * @throws Exception
     */
    public function createConnection($tns, array $config, array $options)
    {
        [$username, $password] = [
            $config['username'] ?? null, $config['password'] ?? null,
        ];

        try {
            return $this->createPdoConnection(
                $tns,
                $username,
                $password,
                $options
            );
        } catch (Exception $e) {
            return $this->tryAgainIfCausedByLostConnection(
                $e,
                $tns,
                $username,
                $password,
                $options
            );
        }
    }

    /**
     * Get encoding connection oracle.
     *
     * @param array $config
     * @return string
     */
    protected function getEncoding(array $config): string
    {
        return $config['charset'] ?? '';
    }

    /**
     * Configure the timezone on the connection.
     *
     * @param PDO $connection
     * @param array $config
     * @return void
     */
    protected function configureTimezone(PDO $connection, array $config): void
    {
        if (isset($config['timezone'])) {
            $timezone = $config['timezone'];

            $connection->prepare("ALTER SESSION SET TIME_ZONE = '{$config['timezone']}'")->execute();
        }
    }

    /**
     * Configure oracle session date format.
     *
     * @param PDO $connection
     * @param ?string $format
     * @return void
     */
    public function configureDateFormat(PDO $connection, ?string $format = 'YYYY-MM-DD HH24:MI:SS'): void
    {
        $nlsFormat = [
            'NLS_DATE_FORMAT' => $format,
            'NLS_TIMESTAMP_FORMAT' => $format,
        ];

        foreach ($nlsFormat as $key => $format) {
            $connection->prepare("ALTER SESSION SET {$key} = '{$format}'")->execute();
        }
    }

    /**
     * get a TNS string connection oracle.
     *
     * @param array $config
     * @return string
     */
    protected function getTNS(array $config): string
    {
        extract($config, EXTR_SKIP);

        $tns = "oci:dbname=" . "(DESCRIPTION =
            (ADDRESS = (PROTOCOL = TCP)(HOST = {$host})(PORT = {$port}))
            (CONNECT_DATA = 
                (SERVER = DEDICATED)
                (SERVICE_NAME = {$service_name})
                (SID = {$sid})
            )
        );
        {$this->getEncoding($config)}";

        return $tns;
    }

    /**
     * Configure the auto commit setting.
     *
     * @param array $config
     * @return void
     */
    protected function configureAutoCommit(array $config): void
    {
        if (!isset($config['auto_commit'])) {
            return;
        }

        $this->options[PDO::ATTR_AUTOCOMMIT] = (bool) $config['auto_commit'];
    }
}