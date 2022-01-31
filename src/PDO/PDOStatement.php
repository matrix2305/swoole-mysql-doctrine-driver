<?php

namespace Doctrine\DBAL\Driver\Swoole\Coroutine\Mysql\PDO;

use PDO as BasePDO;
use PDOException;
use PDOStatement as BaseStatement;
use Swoole\Coroutine\MySQL\Statement;

class PDOStatement extends BaseStatement
{
    public Statement $statement;
    public float $timeout;
    public array $bind_map;
    public int $cursor;
    public int $orientation = BasePDO::FETCH_ORI_NEXT;
    public array $result;
    public int $fetch_mode = BasePDO::FETCH_BOTH;

    public function __construct(PDO $parent, Statement $stmt, array $driver_options = [])
    {
        $this->parent = $parent;
        $this->statement = $stmt;
        $this->timeout = $driver_options['timeout'] ?? -1;
    }

    public function errorCode()
    {
        return $this->statement->errno;
    }

    public function errorInfo()
    {
        return $this->statement->error;
    }

    public function rowCount(): int
    {
        return $this->statement->affected_rows;
    }

    public function bindParam($param, &$var, $type = null, $maxLength = null, $driverOptions = null): bool
    {
        if (! is_string($param) && ! is_int($param)) {
            return false;
        }

        $param = ltrim($param, ':');
        $this->bind_map[$param] = &$var;

        return true;
    }

    public function bindValue($param, $value, $type = null): bool
    {
        if (! is_string($param) && ! is_int($param)) {
            return false;
        }

        if (is_object($value)) {
            if (! method_exists($value, '__toString')) {
                return false;
            } else {
                $value = (string) $value;
            }
        }

        $param = ltrim($param, ':');
        $this->bind_map[$param] = $value;

        return true;
    }

    public function execute($params = null): bool
    {
        if (! empty($params)) {
            foreach ($params as $key => $value) {
                $this->bindParam($key, $value);
            }
        }

        $params = [];
        if (! empty($this->statement->bindKeyMap)) {
            foreach ($this->statement->bindKeyMap as $nameKey => $numKey) {
                if (isset($this->bindMap[$nameKey])) {
                    $params[$numKey] = $this->bindMap[$nameKey];
                }
            }
        } else {
            $params = $this->bindMap;
        }

        $result = $this->statement->execute($params, $this->timeout);
        $this->result = ($ok = $result !== false) ? $result : [];
        $this->afterExecute();

        if ($result === false) {
            throw new PDOException($this->errorInfo(), $this->errorCode());
        }

        return $ok;
    }

    public function setFetchMode($mode, $className = null, array $params = [])
    {
        $this->fetch_mode = $mode;
    }

    public function fetch(
        $mode = null,
        $cursorOrientation = null,
        $cursorOffset = null,
        $fetchArgument = null
    )
    {
        $this->executeWhenStringQueryEmpty();

        $cursorOrientation = is_null($cursorOrientation) ? BasePDO::FETCH_ORI_NEXT : $cursorOrientation;
        $cursorOffset = is_null($cursorOffset) ? 0 : (int) $cursorOffset;

        switch ($cursorOrientation) {
            case BasePDO::FETCH_ORI_ABS:
                $this->cursor = $cursorOffset;
                break;
            case BasePDO::FETCH_ORI_REL:
                $this->cursor += $cursorOffset;
                break;
            case BasePDO::FETCH_ORI_NEXT:
            default:
                $this->cursor++;
        }

        if (isset($this->resultSet[$this->cursor])) {
            $result = $this->resultSet[$this->cursor];
            unset($this->resultSet[$this->cursor]);
        } else {
            $result = false;
        }

        if (empty($result)) {
            return $result;
        } else {
            return $this->transStyle([$result], $mode, $fetchArgument)[0];
        }
    }

    public function fetchColumn($column = null)
    {+
        $column = is_null($column) ? 0 : $column;
        $this->executeWhenStringQueryEmpty();

        return $this->fetch(BasePDO::FETCH_COLUMN, BasePDO::FETCH_ORI_NEXT, 0, $column);
    }

    public function fetchAll($mode = BasePDO::FETCH_BOTH, $fetch_argument = null, ...$args)
    {
        $this->executeWhenStringQueryEmpty();
        $result = $this->transStyle($this->result, $mode, $fetch_argument, $args);
        $this->result = [];

        return $result;
    }

    private function afterExecute(): void
    {
        $this->cursor = -1;
        $this->bind_map = [];
    }

    private function executeWhenStringQueryEmpty()
    {
        if (is_string($this->statement) && empty($this->result)) {
            $this->result = $this->parent->getConnection()->query($this->statement);
            $this->afterExecute();
        }
    }

    private function transBoth($rawData): array
    {
        $temp = [];
        foreach ($rawData as $row) {
            $rowSet = [];
            $i = 0;
            foreach ($row as $key => $value) {
                $rowSet[$key] = $value;
                $rowSet[$i++] = $value;
            }
            $temp[] = $rowSet;
        }

        return $temp;
    }

    private function transStyle(
        $rawData,
        $fetch_mode = null,
        $fetch_argument = null,
        $ctor_args = null
    )
    {
        if (! is_array($rawData)) {
            return false;
        }
        if (empty($rawData)) {
            return $rawData;
        }

        $fetch_mode = is_null($fetch_mode) ? $this->fetch_mode : $fetch_mode;
        $ctor_args = is_null($ctor_args) ? [] : $ctor_args;

        $resultSet = [];
        switch ($fetch_mode) {
            case BasePDO::FETCH_BOTH:
                $resultSet = $this->transBoth($rawData);
                break;
            case BasePDO::FETCH_COLUMN:
                $resultSet = array_column(
                    is_numeric($fetch_argument) ? $this->transBoth($rawData) : $rawData,
                    $fetch_argument
                );
                break;
            case BasePDO::FETCH_OBJ:
                foreach ($rawData as $row) {
                    $resultSet[] = (object) $row;
                }
                break;
            case BasePDO::FETCH_NUM:
                foreach ($rawData as $row) {
                    $resultSet[] = array_values($row);
                }
                break;
            case BasePDO::FETCH_ASSOC:
            default:
                return $rawData;
        }

        return $resultSet;
    }


}