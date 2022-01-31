<?php

namespace Doctrine\DBAL\Driver\Swoole\Coroutine\Mysql\PDO;

use Doctrine\DBAL\Driver\Swoole\Coroutine\Mysql\ConnectionException;
use Doctrine\DBAL\Driver\Swoole\Coroutine\Mysql\PDO\Exception\StatementException;
use Doctrine\DBAL\Query\QueryException;
use Exception;
use InvalidArgumentException;
use PDO as BasePDO;
use Swoole\ConnectionPool;
use Swoole\Coroutine\MySQL;

class PDO extends BasePDO
{
    public static array $keyMap = [
        'dbname' => 'database',
    ];

    private static array $options = [
        'host' => '',
        'port' => 3306,
        'user' => '',
        'password' => '',
        'database' => '',
        'charset' => 'utf8mb4',
        'strict_type' => true,
        'timeout' => -1,
    ];

    private MySQL $client;

    private static ConnectionPool $pool;
    public bool $in_transaction = false;

    /**
     * @throws ConnectionException
     */
    public function __construct($dsn, $username = null, $password = null, $options = null)
    {
        parent::__construct($dsn, $username, $password, $options);
        $this->setClient();

        $this->connect($this->getOptions(...func_get_args()));
    }

    protected function setClient(?MySQL $client = null): void
    {
        $this->client = $client ?: new MySQL();
    }

    /**
     * @throws ConnectionException
     */
    protected function connect(array $options = []): self
    {
        $this->client->connect($options);
        if (! $this->client->connected) {
            $message = $this->client->connect_error ?: $this->client->error;
            $errorCode = $this->client->connect_errno ?: $this->client->errno;

            throw new ConnectionException($message, $errorCode);
        }

        return $this;
    }

    protected function getOptions($dsn, $username, $password, $driverOptions): array
    {
        $dsn = explode(':', $dsn);
        $driver = ucwords(array_shift($dsn));
        $dsn = explode(';', implode(':', $dsn));
        $configuredOptions = [];

        static::checkDriver($driver);

        foreach ($dsn as $kv) {
            $kv = explode('=', $kv);
            if (count($kv)) {
                $configuredOptions[$kv[0]] = $kv[1] ?? '';
            }
        }

        $authorization = [
            'user' => $username,
            'password' => $password,
        ];

        $configuredOptions = $driverOptions + $authorization + $configuredOptions;

        foreach (static::$keyMap as $pdoKey => $swpdoKey) {
            if (isset($configuredOptions[$pdoKey])) {
                $configuredOptions[$swpdoKey] = $configuredOptions[$pdoKey];
                unset($configuredOptions[$pdoKey]);
            }
        }

        return array_merge(static::$options, $configuredOptions);
    }

    public static function checkDriver(string $driver): void
    {
        if (!in_array($driver, static::getAvailableDrivers(), true)) {
            throw new InvalidArgumentException("{$driver} driver is not supported yet.");
        }
    }

    public static function getAvailableDrivers(): array
    {
        return ['Mysql'];
    }

    public function beginTransaction(): void
    {
        $this->client->begin();
        $this->in_transaction = true;
    }

    public function rollBack(): void
    {
        $this->client->rollback();
        $this->in_transaction = false;
    }

    public function commit(): void
    {
        $this->client->commit();
        $this->in_transaction = true;
    }

    public function inTransaction(): bool
    {
        return $this->in_transaction;
    }

    public function lastInsertId($name = null)
    {
        return $this->client->insert_id;
    }

    public function errorCode()
    {
        return $this->client->errno;
    }

    public function errorInfo(): array
    {
        return [
            $this->client->errno,
            $this->client->errno,
            $this->client->error,
        ];
    }

    /**
     * @throws QueryException
     */
    public function exec($statement): int
    {
        $this->query($statement);

        return $this->client->affected_rows;
    }

    /**
     * @throws QueryException
     */
    public function query($statement, $mode = BasePDO::ATTR_DEFAULT_FETCH_MODE, $arg3 = null, array $ctorargs = [])
    {
        $result = $this->client->query($statement, self::$options['timeout']);

        if ($result === false) {
            $exception = new Exception($this->client->error, $this->client->errno);
            throw new QueryException($statement, [], $exception);
        }

        return $result;
    }

    /**
     * @throws QueryException
     */
    public function prepare($query, $options = null): PDOStatement
    {
        $options = is_null($options) ? [] : $options;
        if (strpos($query, ':') !== false) {
            $i = 0;
            $bindKeyMap = [];
            $query = preg_replace_callback('/:([a-zA-Z_]\w*?)\b/', static function ($matches) use (&$i, &$bindKeyMap) {
                $bindKeyMap[$matches[1]] = $i++;

                return '?';
            }, $query);
        }

        $stmtObj = $this->client->prepare($query);

        if ($stmtObj) {
            $stmtObj->bindKeyMap = $bindKeyMap ?? [];

            return new PDOStatement($this, $stmtObj, $options);
        }

        $statementException = new StatementException($this->client->error, $this->client->errno);
        throw new QueryException($query, [], $statementException);
    }

    public function getAttribute($attribute)
    {
        switch ($attribute) {
            case BasePDO::ATTR_AUTOCOMMIT:
                return true;
            case BasePDO::ATTR_CASE:
            case BasePDO::ATTR_CLIENT_VERSION:
            case BasePDO::ATTR_CONNECTION_STATUS:
                return $this->client->connected;
            case BasePDO::ATTR_DRIVER_NAME:
            case BasePDO::ATTR_ERRMODE:
                return 'Swoole Style';
            case BasePDO::ATTR_ORACLE_NULLS:
            case BasePDO::ATTR_PERSISTENT:
            case BasePDO::ATTR_PREFETCH:
            case BasePDO::ATTR_SERVER_INFO:
                return self::$options['timeout'];
            case BasePDO::ATTR_SERVER_VERSION:
                return 'Swoole Mysql';
            case BasePDO::ATTR_TIMEOUT:
            default:
                throw new InvalidArgumentException('Not implemented yet!');
        }
    }

    public function quote($string, $type = null)
    {
        throw new \BadMethodCallException(
            <<<TXT
If you are using this function to build SQL statements,
you are strongly recommended to use PDO::prepare() to prepare SQL statements
with bound parameters instead of using PDO::quote() to interpolate user input into an SQL statement.
Prepared statements with bound parameters are not only more portable, more convenient,
immune to SQL injection, but are often much faster to execute than interpolated queries,
as both the server and client side can cache a compiled form of the query.
TXT
        );
    }

    public function __destruct()
    {
        self::$pool->close();
    }

}