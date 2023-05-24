<?php declare(strict_types=1);

namespace Doctrine\DBAL\Driver\SwooleMySQL;

use Doctrine\DBAL\Driver\AbstractMySQLDriver;
use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\Deprecations\Deprecation;
use Doctrine\DBAL\Driver\PDO;


final class Driver extends AbstractMySQLDriver
{
    public static ConnectionPool $pool;

    public function connect(array $params, $username = null, $password = null, array $driverOptions = []): Connection
    {
        if (!isset(self::$pool)) {
            $poolSize = $params['poolSize'] ?? 10;
            self::$pool = new ConnectionPool($poolSize);
        }


        if (!self::$pool->getConnection()) {
            $this->setConnection($params, $username, $password, $driverOptions);
        }


        return self::$pool->getConnection();
    }

    /**
     * @throws Exception
     */
    public function setConnection(array $params, $username = null, $password = null, array $driverOptions = []) : void
    {
        try {
            $conn = new PDO\Connection(
                $this->constructPdoDsn($params),
                $username,
                $password,
                $driverOptions
            );
        } catch (\PDOException $e) {
            throw Exception::driverException($this, $e);
        }

        self::$pool->setConnection($conn);
    }

    protected function constructPdoDsn(array $params)
    {
        $dsn = 'mysql:';
        if (isset($params['host']) && $params['host'] !== '') {
            $dsn .= 'host=' . $params['host'] . ';';
        }

        if (isset($params['port'])) {
            $dsn .= 'port=' . $params['port'] . ';';
        }

        if (isset($params['dbname'])) {
            $dsn .= 'dbname=' . $params['dbname'] . ';';
        }

        if (isset($params['unix_socket'])) {
            $dsn .= 'unix_socket=' . $params['unix_socket'] . ';';
        }

        if (isset($params['charset'])) {
            $dsn .= 'charset=' . $params['charset'] . ';';
        }

        return $dsn;
    }

    /**
     * {@inheritdoc}
     *
     * @deprecated
     */
    public function getName()
    {
        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/issues/3580',
            'Driver::getName() is deprecated'
        );

        return 'pdo_mysql';
    }
}