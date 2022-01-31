<?php

declare(strict_types=1);

use Swoole\Coroutine as Co;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Driver\Swoole\Coroutine\Mysql\Driver;

require_once __DIR__ . '/../vendor/autoload.php';

$params = [
    'dbname' => 'scalefast',
    'user' => 'root',
    'password' => 'kGWEhUy2nM8b7aZQ',
    'host' => '127.0.0.1',
    'driverClass' => Driver::class,
    'poolSize' => 8,
];

$conn = DriverManager::getConnection($params);

Co\run(static function() use ($conn): void {
    $results = [];

    $wg = new Co\WaitGroup();
    $start_time = time();

    foreach (range(0, 8) as $i) {
        Co::create(static function() use (&$results, $wg, $conn, $i): void {
            $start = $i*10;
            $wg->add();
            $foo = $conn->executeQuery('select * from message limit ' . $start . ',10')->fetchAllAssociative();
            $results[] = $foo;
            $wg->done();
        });
    }

    $wg->wait();
    $elapsed = time() - $start_time;
    $sum = array_sum($results);

    echo "$i queries in $elapsed second, returning: $sum\n";
    print_r($results);
});