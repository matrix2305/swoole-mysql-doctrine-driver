<?php
declare(strict_types=1);
namespace Doctrine\DBAL\Driver\SwooleMySQL;

use Doctrine\DBAL\Driver\PDO\Connection;

class ConnectionPool
{
    private $poolSize;
    private $connections = [];

    public function __construct($poolSize)
    {
        $this->poolSize = $poolSize;
    }

    public function getConnection(): ?Connection
    {
        if (!empty($this->connections)) {
            return array_shift($this->connections);
        }

        return null;
    }

    public function setConnection(Connection $connection): void
    {
        if (count($this->connections) < $this->poolSize) {
            $this->connections[] = $connection;
        }
    }
}