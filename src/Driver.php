<?php declare(strict_types=1);

namespace Doctrine\DBAL\Driver\Swoole\Coroutine\Mysql;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\AbstractMySQLDriver;
use Doctrine\DBAL\Driver\ResultStatement;
use Swoole\Coroutine;
use Swoole\Coroutine\MySQL;

final class Driver extends AbstractMySQLDriver
{
    public function connect(array $params, ?string $username = null, ?string $password = null, array $driverOptions = []): Connection
    {
        $mysql = new MySQL();
        $mysql->connect([
            'host' => $params['host'],
            'port' => $params['port'],
            'user' => $username,
            'password' => $password,
            'database' => $params['dbname'],
            // Add more connection options as needed
        ]);

        return SwooleConnection($mysql);
    }

    public function getName()
    {
        // TODO: Implement getName() method.
    }
}