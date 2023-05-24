<?php declare(strict_types=1);

namespace Doctrine\DBAL\Driver\SwooleMySQL;

use Doctrine\DBAL\Driver\AbstractMySQLDriver;
use Doctrine\DBAL\Driver\Connection;
use Swoole\Coroutine\MySQL;


final class Driver extends AbstractMySQLDriver
{
    public static SwooleConnection $connection;

    public function connect(array $params, $username = null, $password = null, array $driverOptions = []): Connection
    {
        if (isset(self::$connection)) {
            return self::$connection;
        }

        $this->setConnection($params, $username, $password, $driverOptions);


        return $this->connect($params, $username, $password, $driverOptions);
    }

    public function setConnection(array $params, $username = null, $password = null, array $driverOptions = []) : void
    {
        go(static function () use($params, $username, $password, $driverOptions) {
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

            self::$connection = $connection;
        });
    }

    public function getName(): string
    {
        return 'swoole_mysql';
    }
}