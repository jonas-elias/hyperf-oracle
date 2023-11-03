<?php

declare(strict_types=1);

namespace Hyperf\Database\Oracle;

use Hyperf\Database\Oracle\Connectors\OracleConnector;
use Hyperf\Database\Oracle\Listener\RegisterConnectorListener;

/**
 * class ConfigProvider
 *
 * @author jonas-elias
 */
class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
                'db.connector.oracle' => OracleConnector::class,
            ],
            'listeners' => [
                RegisterConnectorListener::class,
            ],
        ];
    }
}
