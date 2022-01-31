<?php declare(strict_types=1);

namespace Doctrine\DBAL\Driver\Swoole\Coroutine\Mysql;

use Doctrine\DBAL\Driver\Result as ResultInterface;
use Doctrine\DBAL\Driver\Swoole\Coroutine\Mysql\Exception\DriverException;
use PDO;
use PDOException;

class Result implements ResultInterface
{
    private \PDOStatement $stmt;

    public function __construct(\PDOStatement $stmt)
    {
        $this->stmt = $stmt;
    }

    /**
     * @throws DriverException
     */
    public function fetchNumeric()
    {
        return $this->fetch(PDO::FETCH_NUM);
    }

    /**
     * @throws DriverException
     */
    public function fetchAssociative()
    {
        return $this->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * @throws DriverException
     */
    public function fetchOne()
    {
        return $this->fetch(PDO::FETCH_COLUMN);
    }

    /**
     * @throws DriverException
     */
    public function fetchAllNumeric(): array
    {
        return $this->fetchAll(PDO::FETCH_NUM);
    }

    /**
     * @throws DriverException
     */
    public function fetchAllAssociative(): array
    {
        return $this->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @throws DriverException
     */
    public function fetchFirstColumn(): array
    {
        return $this->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * @throws DriverException
     */
    public function rowCount(): int
    {
        try {
            return $this->stmt->rowCount();
        } catch (PDOException $exception) {
            throw DriverException::new($exception);
        }

    }

    /**
     * @throws DriverException
     */
    public function columnCount(): int
    {
        try {
            return $this->stmt->columnCount();
        } catch (PDOException $exception) {
            throw DriverException::new($exception);
        }
    }

    public function free(): void
    {
        $this->stmt->closeCursor();
    }

    /**
     * @throws DriverException
     */
    private function fetch(int $mode)
    {
        try {
            return $this->stmt->fetch($mode);
        } catch (PDOException $exception) {
            throw DriverException::new($exception);
        }
    }

    /**
     * @throws DriverException
     */
    private function fetchAll(int $mode): array
    {
        try {
            $data = $this->stmt->fetchAll($mode);
        } catch (PDOException $exception) {
            throw DriverException::new($exception);
        }

        assert(is_array($data));

        return $data;
    }



}
