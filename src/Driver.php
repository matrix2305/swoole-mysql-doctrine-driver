<?php declare(strict_types=1);

namespace Doctrine\DBAL\Driver\Swoole\Coroutine\Mysql;

use Doctrine\DBAL\Driver\AbstractMySQLDriver;
use Doctrine\DBAL\Driver\Connection as ConnectionInterface;
use Doctrine\DBAL\Driver\Swoole\Coroutine\Mysql\Exception\ConnectionException;
use Doctrine\DBAL\Driver\Swoole\Coroutine\Mysql\Exception\DriverException;
use Doctrine\Deprecations\Deprecation;
use PDO;
use Swoole\ConnectionPool;
use Swoole\Coroutine;
use Laravel\Octane\Facades\Octane;

final class Driver extends AbstractMySQLDriver
{
    public const DEFAULT_POOL_SIZE = 8;
    private static ConnectionPool $pool;

    public function connect(array $params, $username = null, $password = null, array $driverOptions = []): ConnectionInterface
    {
        if (!empty($driverOptions)) {
            $params['driverOptions'] = $driverOptions;
        }
        if ($username) {
            $params['user'] = $username;
        }
        if ($password) {
            $params['password'] = $password;
        }

        if (!isset(self::$pool)) {
            [$pool] = Octane::concurrently([function () use($params) {
                $pool = new ConnectionPool(
                    fn(): Connection => $this->createConnection($this->dsn($params), $params),
                    $params['poolSize'] ?? self::DEFAULT_POOL_SIZE,
                );
                return $pool;
            }]);

            self::$pool = $pool;
        }

        $connection = self::$pool->get();
        defer(static fn() => self::$pool->put($connection));
        return $connection;
    }

    /**
     * @throws DriverException
     * @throws ConnectionException
     */
    public function createConnection(string $dsn, array $params): Connection
    {
        $driverOptions = $params['driverOptions'] ?? [];

        if (! empty($params['persistent'])) {
            $driverOptions[PDO::ATTR_PERSISTENT] = true;
        }

        return new Connection(
            $dsn,
            $params['user'] ?? '',
            $params['password'] ?? '',
            $driverOptions
        );
    }

    private function dsn(array $params): string
    {
        if (array_key_exists('url', $params)) {
            return $params['url'];
        }

        $params['host'] ??= '127.0.0.1';
        $params['port'] ??= 3306;
        $params['dbname'] ??= 'mysql';

        $dsn = 'mysql:';
        if (isset($params['host']) && $params['host'] !== '') {
            $dsn .= 'host=' . $params['host'] . ';';
        }

        if (isset($params['port'])) {
            $dsn .= 'port=' . $params['port'] . ';';
        }

        if (isset($params['dbname'])) {
            $dsn .= 'dbname=' . $params['dbname'] . ';';
        }

        if (isset($params['unix_socket'])) {
            $dsn .= 'unix_socket=' . $params['unix_socket'] . ';';
        }

        if (isset($params['charset'])) {
            $dsn .= 'charset=' . $params['charset'] . ';';
        }

        return $dsn;
    }

    public function getName()
    {
        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/issues/3580',
            'Driver::getName() is deprecated'
        );

        return 'mysqli';
    }
}