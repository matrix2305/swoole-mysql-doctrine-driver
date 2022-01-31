<?php declare(strict_types=1);

namespace Doctrine\DBAL\Driver\Swoole\Coroutine\Mysql;

use Doctrine\DBAL\Driver\Result as ResultInterface;
use Doctrine\DBAL\Driver\Statement as StatementInterface;
use Doctrine\DBAL\Driver\Swoole\Coroutine\Mysql\PDO\Exception\DriverException;
use Doctrine\DBAL\Driver\Swoole\Coroutine\Mysql\PDO\Exception\UnknownParameterTypeException;
use Doctrine\DBAL\Driver\Swoole\Coroutine\Mysql\PDO\PDOStatement;
use Doctrine\DBAL\ParameterType;
use Doctrine\Deprecations\Deprecation;
use PDOException;

final class Statement implements StatementInterface
{
    private const PARAM_TYPE_MAP = [
        ParameterType::NULL => PDO::PARAM_NULL,
        ParameterType::INTEGER => PDO::PARAM_INT,
        ParameterType::STRING => PDO::PARAM_STR,
        ParameterType::ASCII => PDO::PARAM_STR,
        ParameterType::BINARY => PDO::PARAM_LOB,
        ParameterType::LARGE_OBJECT => PDO::PARAM_LOB,
        ParameterType::BOOLEAN => PDO::PARAM_BOOL,
    ];

    private PDOStatement  $stmt;
    private array $params;

    public function __construct(PDOStatement $stmt)
    {
        $this->stmt = $stmt;
    }

    /**
     * @throws UnknownParameterTypeException
     * @throws DriverException
     */
    public function bindValue($param, $value, $type = ParameterType::STRING): bool
    {
        $type = $this->convertParamType($type);

        try {
            return $this->stmt->bindValue($param, $value, $type);
        } catch (PDOException $exception) {
            throw DriverException::new($exception);
        }
    }

    /**
     * @throws UnknownParameterTypeException
     * @throws DriverException
     */
    public function bindParam($param, &$variable, $type = ParameterType::STRING, $length = null, $driverOptions = null): bool
    {
        if (func_num_args() > 4) {
            Deprecation::triggerIfCalledFromOutside(
                'doctrine/dbal',
                'https://github.com/doctrine/dbal/issues/4533',
                'The $driverOptions argument of Statement::bindParam() is deprecated.'
            );
        }

        $type = $this->convertParamType($type);

        try {
            return $this->stmt->bindParam($param, $variable, $type, ...array_slice(func_get_args(), 3));
        } catch (PDOException $exception) {
            throw DriverException::new($exception);
        }
    }

    /**
     * @throws DriverException
     */
    public function execute($params = null): ResultInterface
    {
        try {
            $this->stmt->execute($params);
        } catch (PDOException $exception) {
            throw DriverException::new($exception);
        }

        return new Result($this->stmt);
    }

    /**
     * @throws UnknownParameterTypeException
     */
    private function convertParamType(int $type): int
    {
        if (! isset(self::PARAM_TYPE_MAP[$type])) {
            throw UnknownParameterTypeException::new($type);
        }

        return self::PARAM_TYPE_MAP[$type];
    }

}