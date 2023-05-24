<?php declare(strict_types=1);

namespace Doctrine\DBAL\Driver\SwooleMySQL;

use Doctrine\DBAL\Driver\AbstractMySQLDriver;
use Doctrine\DBAL\Driver\Connection;
use Swoole\Coroutine\MySQL;
use function Swoole\Coroutine\run;

final class Driver extends AbstractMySQLDriver
{
    public static ConnectionPool $pool;

    public function connect(array $params, $username = null, $password = null, array $driverOptions = []): Connection
    {
        if (!isset(self::$pool)) {
            $poolSize = $params['poolSize'] ?? 10;
            self::$pool = new ConnectionPool($poolSize);
        }

        if (!self::$pool->getConnection()) {
            $this->setConnection($params, $username, $password, $driverOptions);
        }


        return self::$pool->getConnection();
    }

    public function setConnection(array $params, $username = null, $password = null, array $driverOptions = []) : void
    {
        run(function () use($params, $username, $password, $driverOptions) {
            $mysql = new MySQL();

            // Set the connection parameters
            $host = $params['host'] ?? '127.0.0.1';
            $port = $params['port'] ?? 3306;
            $database = $params['dbname'] ?? '';
            $user = $username ?? '';
            $passwd = $password ?? '';

            $mysql->connect([
                'host' => $host,
                'port' => $port,
                'user' => $user,
                'password' => $passwd,
                'database' => $database,
            ]);

            $connection = new SwooleConnection($mysql);

            self::$pool->setConnection($connection);
        });
    }

    public function getName(): string
    {
        return 'swoole_mysql';
    }
}