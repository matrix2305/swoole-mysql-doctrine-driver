<?php declare(strict_types=1);

namespace Doctrine\DBAL\Driver\SwooleMySQL;

use Doctrine\DBAL\Driver\AbstractMySQLDriver;
use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Driver\PingableConnection;
use Doctrine\DBAL\Driver\ServerInfoAwareConnection;
use Doctrine\DBAL\ParameterType;
use Swoole\Coroutine\MySQL;

final class Driver extends AbstractMySQLDriver
{
    public function connect(array $params, $username = null, $password = null, array $driverOptions = []): Connection
    {
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

        return new SwooleConnection($mysql);
    }

    public function getName(): string
    {
        return 'swoole_mysql';
    }
}