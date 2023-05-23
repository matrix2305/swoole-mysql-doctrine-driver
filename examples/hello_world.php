<?php

declare(strict_types=1);

use Swoole\Coroutine as Co;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Driver\SwooleMySQL\Driver;

require_once __DIR__ . '/../vendor/autoload.php';

$params = [
    'dbname' => 'your_db',
    'user' => 'your_user',
    'password' => 'your_passwd',
    'host' => '127.0.0.1',
    'driverClass' => Driver::class,
    'poolSize' => 10,
];

$conn = DriverManager::getConnection($params);

Co\run(static function() use ($conn): void {
    $results = [];

    $wg = new Co\WaitGroup();
    $start_time = microtime(true);

    foreach (range(0, 10) as $i) {
        Co::create(static function() use (&$results, $wg, $conn, $i): void {
            $start = $i*10;
            $wg->add();
            $foo = $conn->executeQuery('select * from message limit ' . $start . ',10')->fetchAllAssociative();
            $results[] = $foo;
            $wg->done();
        });
    }

    $wg->wait();
    $elapsed = microtime(true) - $start_time;
    $sum = array_sum($results);

    echo "$i queries in $elapsed second, returning: $sum\n";
});
