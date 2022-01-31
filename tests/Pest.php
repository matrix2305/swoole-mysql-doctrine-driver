<?php declare(strict_types=1);

namespace Tests;

use Doctrine\DBAL\{Connection, Driver, DriverManager};

function conn(): Connection
{
    $params = [
        'dbname' => 'mysql',
        'user' => 'mysql',
        'password' => 'mysql',
        'host' => 'mysql',
        'driverClass' => Driver\Swoole\Coroutine\Mysql\Driver::class
    ];

    return DriverManager::getConnection($params);
}