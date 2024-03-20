# Oracle driver for Hyperf

[![Latest Stable Version](http://poser.pugx.org/jonas-elias/hyperf-oracle/v)](https://packagist.org/packages/jonas-elias/hyperf-oracle) [![Total Downloads](http://poser.pugx.org/jonas-elias/hyperf-oracle/downloads)](https://packagist.org/packages/jonas-elias/hyperf-oracle) [![Latest Unstable Version](http://poser.pugx.org/jonas-elias/hyperf-oracle/v/unstable)](https://packagist.org/packages/jonas-elias/hyperf-oracle) [![License](http://poser.pugx.org/jonas-elias/hyperf-oracle/license)](https://packagist.org/packages/jonas-elias/hyperf-oracle) [![PHP Version Require](http://poser.pugx.org/jonas-elias/hyperf-oracle/require/php)](https://packagist.org/packages/jonas-elias/hyperf-oracle)

## Hyperf-Oracle

Hyperf-Oracle is an Oracle Database Driver package for Hyperf. Extension of Hyperf\Database that uses pdo-oci extension to communicate with Oracle. Through integration of Swoole, Hyperf optimizes resource utilization and boosts concurrency, leading to enhanced throughput and responsiveness when interfacing with Oracle databases.

## Quick Installation

```bash
composer require jonas-elias/hyperf-oracle
```

## Example

```php
use Hyperf\DbConnection\Db;

// select
Db::table('users')->get();
// insert
Db::table('users')->insert(['name' => 'jonas']);
// update
Db::table('users')->where('id', '=', 1)->update(['name' => 'johnny']);
// delete
Db::table('users')->delete(1);
```

## Environment Settings

The following environment variables should be configured to specify the connection details for the Oracle database:

```ini
DB_DRIVER=oracle
DB_HOST=oracle.host
DB_PORT=1521
DB_SERVICE_NAME=XE
DB_SID=XE
DB_USERNAME=user
DB_PASSWORD=password
DB_CHARSET=utf8mb4
DB_AUTO_COMMIT=false
DB_TIMEZONE=America/Sao_Paulo
```

## Configuration in Code

In your `databases.php` configuration file, you can set up the database connection using the following format:

```php
return [
    'default' => [
        'driver' => env('DB_DRIVER', 'oracle'),
        'host' => env('DB_HOST', 'host'),
        'port' => env('DB_PORT', 1521),
        'database' => env('DB_DATABASE', 'hyperf'),
        'username' => env('DB_USERNAME', 'oracle'),
        'service_name' => env('DB_SERVICE_NAME', 'XE'),
        'sid' => env('DB_SID', 'XE'),
        'auto_commit' => env('DB_AUTO_COMMIT', false),
        'timezone' => env('DB_TIMEZONE', 'America/Sao_Paulo'),
        'password' => env('DB_PASSWORD', 'password'),
        'charset' => env('DB_CHARSET', 'utf8mb4'),
        'prefix' => env('DB_PREFIX', ''),
        'pool' => [
            'min_connections' => 1,
            'max_connections' => 10,
            'connect_timeout' => 10.0,
            'wait_timeout' => 3.0,
            'heartbeat' => -1,
            'max_idle_time' => (float) env('DB_MAX_IDLE_TIME', 60),
        ],
        'commands' => [
            'gen:model' => [
                'path' => 'app/Model',
                'force_casts' => true,
                'inheritance' => 'Model',
                'uses' => '',
                'table_mapping' => [],
            ],
        ],
    ],
];
```

## Credits

- [Jonas Elias](https://github.com/jonas-elias)
- [Arjay Angeles](https://github.com/yajra/laravel-oci8)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
