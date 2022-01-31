<?php declare(strict_types=1);

namespace Doctrine\DBAL\Driver\Swoole\Coroutine\Mysql;

use Doctrine\DBAL\Driver\Connection as ConnectionInterface;
use Doctrine\DBAL\Driver\Result as ResultInterface;
use Doctrine\DBAL\Driver\Statement as StatementInterface;
use Doctrine\DBAL\Driver\Swoole\Coroutine\Mysql\Exception\DriverException;
use Doctrine\DBAL\ParameterType;
use PDO;
use PDOException;
use PDOStatement;

final class Connection implements ConnectionInterface
{
    private PDO $connection;

    /**
     * @throws DriverException
     */
    public function __construct($dsn, $user = null, $password = null, ?array $options = null)
    {
        try {
            $this->connection = new PDO($dsn, (string) $user, (string) $password, (array) $options);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $exception) {
            throw DriverException::new($exception);
        }

    }

    /**
     * @throws DriverException
     */
    public function exec(string $sql): int
    {
        try {
            return $this->connection->exec($sql);
        } catch (PDOException $exception) {
            throw DriverException::new($exception);
        }
    }

    /**
     * @throws DriverException
     */
    public function prepare(string $sql): StatementInterface
    {
        try {
            $stmt = $this->connection->prepare($sql);
            assert($stmt instanceof PDOStatement);

            return $this->createStatement($stmt);
        } catch (PDOException $exception) {
            throw DriverException::new($exception);
        }
    }

    /**
     * @throws DriverException
     */
    public function query(string $sql): ResultInterface
    {
        try {
            $stmt = $this->connection->query($sql);
            assert($stmt instanceof PDOStatement);

            return new Result($stmt);
        } catch (PDOException $exception) {
            throw DriverException::new($exception);
        }
    }

    public function quote($value, $type = ParameterType::STRING)
    {
        return $this->connection->quote($value, $type);
    }

    /**
     * @throws DriverException
     */
    public function lastInsertId($name = null): bool|string
    {
        try {
            if ($name === null) {
                return $this->connection->lastInsertId();
            }

            return $this->connection->lastInsertId($name);
        } catch (PDOException $exception) {
            throw DriverException::new($exception);
        }
    }

    protected function createStatement(PDOStatement $stmt): StatementInterface
    {
        return new Statement($stmt);
    }

    public function getWrappedConnection(): PDO
    {
        return $this->connection;
    }

    public function beginTransaction()
    {
        $this->connection->beginTransaction();
    }

    public function commit()
    {
        $this->connection->commit();
    }

    public function rollBack()
    {
        $this->connection->rollback();
    }
}