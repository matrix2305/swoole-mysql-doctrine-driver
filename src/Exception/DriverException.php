<?php

namespace Doctrine\DBAL\Driver\Swoole\Coroutine\Mysql\Exception;

use PDOException;
use Throwable;

final class DriverException extends \Exception
{
    private ?string $state;

    public function __construct(string $message, string $state = null, int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->state = $state;
    }

    public function getSQLState(): ?string
    {
        return $this->state;
    }

    public static function new(PDOException $exception): self
    {
        if ($exception->errorInfo !== null) {
            [$state, $code] = $exception->errorInfo;
        } else {
            $code     = $exception->getCode();
            $state = null;
        }

        return new self($exception->getMessage(), $state, $code, $exception);
    }

}