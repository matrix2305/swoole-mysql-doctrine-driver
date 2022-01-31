# Swoole Coroutine MySQL Doctrine DBAL Driver

A `Doctrine\DBAL\Driver` implementation on top of `Swoole\Coroutine\MySQL`.

## Getting started

### Install

```shell
composer require diego-ninja/swoole-mysql-doctrine-driver
```

### Usage

Doctrine parameters, for both DBAL and ORM projects, accepts the `driverClass` option; it is where we can inject this project's driver:

```php
use Doctrine\DBAL\{Driver, DriverManager};

$params = [
    'dbname' => 'mysql',
    'user' => 'mysql',
    'password' => 'mysql',
    'host' => 'db',
    'driverClass' => Driver\Swoole\Coroutine\Mysql\Driver::class,
    'poolSize' => 8,
];

$conn = DriverManager::getConnection($params);
```

*Yes, I deliberately used the `Doctrine\DBAL\Driver` namespace + `Swoole\Coroutine\MySQL` namespace, so it is not confusing.*

#### You are ready to rock inside Coroutines (Fibers):

```php
Co\run(static function() use ($conn): void {
    $results = [];
    $wg = new Co\WaitGroup();
    $start_time = time();

    Co::create(static function() use (&$results, $wg, $conn): void {
        $wg->add();
        $results[] = $conn->executeQuery('select 1, sleep(1)')->fetchOne();
        $wg->done();
    });

    Co::create(static function() use (&$results, $wg, $conn): void {
        $wg->add();
        $results[] = $conn->executeQuery('select 1, sleep(1)')->fetchOne();
        $wg->done();
    });

    $wg->wait();
    $elapsed = time() - $start_time;
    $sum = array_sum($results);

    echo "Two sleep(1) queries in $elapsed second, returning: $sum\n";
});
```

You should be seeing `Two sleep(1) queries in 1 second, returning: 2` and the total time should **not** be 2 (the sum of `sleep(1)`'s) because they ran concurrently.

```shell
real    0m1.228s
user    0m0.036s
sys     0m0.027s
```