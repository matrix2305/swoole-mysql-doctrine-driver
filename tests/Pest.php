<?php declare(strict_types=1);

namespace Tests;

use Doctrine\DBAL\{Connection, Driver, DriverManager};

function conn(): Connection
{
    $params = [
        'dbname' => 'scalefast',
        'user' => 'root',
        'password' => 'kGWEhUy2nM8b7aZQ',
        'host' => '127.0.0.1',
        'driverClass' => Driver\SwooleMySQL\Driver::class
    ];

    return DriverManager::getConnection($params);
}