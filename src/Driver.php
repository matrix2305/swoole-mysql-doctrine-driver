<?php declare(strict_types=1);

namespace Doctrine\DBAL\Driver\Swoole\Coroutine\Mysql;

use Doctrine\DBAL\Driver\Swoole\Coroutine\Mysql\PDO\Exception\DriverException;
use Doctrine\DBAL\Driver\AbstractMySQLDriver;
use Doctrine\DBAL\Driver\Connection as ConnectionInterface;
use Swoole\ConnectionPool;

final class Driver extends AbstractMySQLDriver
{
    public const DEFAULT_POOL_SIZE = 8;
    private static ConnectionPool $pool;

    public function connect(array $params): ConnectionInterface
    {
        if (!isset(self::$pool)) {
            self::$pool = new ConnectionPool(
                fn(): Connection => $this->createConnection($this->dsn($params)),
                $params['poolSize'] ?? self::DEFAULT_POOL_SIZE,
            );
        }

        $connection = self::$pool->get();
        defer(static fn() => self::$pool->put($connection));
        return $connection;
    }

    /**
     * @throws DriverException
     * @throws ConnectionException
     */
    public function createConnection(string $dsn): Connection
    {
        return new Connection($dsn);
    }

    private function dsn(array $params): string
    {
        if (array_key_exists('url', $params)) {
            return $params['url'];
        }

        $params['host'] ??= '127.0.0.1';
        $params['port'] ??= 3306;
        $params['dbname'] ??= 'mysql';
        $params['user'] ??= 'mysql';
        $params['password'] ??= 'mysql';

        return implode(';', [
            "host={$params['host']}",
            "port={$params['port']}",
            "dbname={$params['dbname']}",
            "user={$params['user']}",
            "password={$params['password']}",
        ]);
    }
}