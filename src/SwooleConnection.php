<?php declare(strict_types=1);

namespace Doctrine\DBAL\Driver\Swoole\Coroutine\Mysql;

use Doctrine\DBAL\Connection;
use Swoole\Coroutine\MySQL;
use Doctrine\DBAL\Driver\ResultStatement;

final class SwooleConnection extends Connection
{
    private $mysql;

    public function __construct(MySQL $mysql, ?string $username = null, ?string $password = null, array $driverOptions = [])
    {
        $this->mysql = $mysql;

        $driver = new Driver(); // Create an instance of the SwooleDriver

        parent::__construct([], $driver, null, null); // Calling parent constructor

        // Apply any additional connection settings if needed
    }

    public function executeQuery($sql, array $params = [], $types = [], ?\Doctrine\DBAL\Cache\QueryCacheProfile $queryCacheProfile = null): ResultStatement
    {
        $statement = $this->prepare($sql);

        if (count($params) > 0) {
            foreach ($params as $key => $value) {
                $statement->bindValue($key, $value, $types[$key] ?? null);
            }
        }

        $statement->execute();

        return $statement;
    }

    public function prepare($sql): SwooleStatement
    {
        return new SwooleStatement($this->mysql, $sql);
    }
}