<?php

declare(strict_types=1);

namespace Hyperf\Database\Oracle\Listener;

use Hyperf\Database\Connection;
use Hyperf\Database\Oracle\OracleSqlConnection;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Framework\Event\BootApplication;
use Psr\Container\ContainerInterface;

class RegisterConnectorListener implements ListenerInterface
{
    /**
     * Create a new connection factory instance.
     */
    public function __construct(protected ContainerInterface $container) {}

    public function listen(): array
    {
        return [
            BootApplication::class,
        ];
    }

    /**
     * register oracle Connector
     */
    public function process(object $event): void
    {
        Connection::resolverFor('oracle', static function ($connection, $database, $prefix, $config) {
            return new OracleSqlConnection($connection, $database, $prefix, $config);
        });
    }
}
