<?php declare(strict_types=1);

namespace Doctrine\DBAL\Driver\SwooleMySQL\Exception;

final class ConnectionException extends \Exception
{
    public static function failed(string $dsn): self
    {
        return new self("Unable to connect to MySQL: [$dsn]");
    }
}