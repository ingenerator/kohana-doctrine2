<?php

namespace Ingenerator\KohanaDoctrine;


use PDO;

class NullPDO extends \PDO
{
    /**
     * @var string
     */
    private $driver;

    public function __construct($driver_name)
    {
        if ($driver_name === 'pdo_mysql') {
            $this->driver = 'mysql';
        } else {
            throw new \InvalidArgumentException(__CLASS__.' only supports pdo_mysql, got `'.$driver_name.'`');
        }
    }

    public function setAttribute($attribute, $value): bool
    {
        // No-op
	    return FALSE;
    }

    public function getAttribute($attribute): mixed
    {
	    if ($attribute == static::ATTR_DRIVER_NAME) {
		    return $this->driver;
	    }

        throw DatabaseNotConfiguredException::forMethod(__METHOD__);
    }

    public function prepare($statement, $driver_options = []): \PDOStatement|false
    {
        throw DatabaseNotConfiguredException::forMethod(__METHOD__);
    }

    public function beginTransaction(): bool
    {
        throw DatabaseNotConfiguredException::forMethod(__METHOD__);
    }

    public function commit(): bool
    {
        throw DatabaseNotConfiguredException::forMethod(__METHOD__);
    }

    public function rollBack(): bool
    {
        throw DatabaseNotConfiguredException::forMethod(__METHOD__);
    }

    public function inTransaction(): bool
    {
        throw DatabaseNotConfiguredException::forMethod(__METHOD__);
    }

    public function exec($statement): int|false
    {
        throw DatabaseNotConfiguredException::forMethod(__METHOD__);
    }

    public function query(string $query, ?int $fetchMode = null, mixed ...$fetchModeArgs): \PDOStatement|false
    {
        throw DatabaseNotConfiguredException::forMethod(__METHOD__);
    }

    public function lastInsertId($name = NULL): string|false
    {
        throw DatabaseNotConfiguredException::forMethod(__METHOD__);
    }

    public function errorCode(): ?string
    {
        throw DatabaseNotConfiguredException::forMethod(__METHOD__);
    }

    public function errorInfo(): array
    {
        throw DatabaseNotConfiguredException::forMethod(__METHOD__);
    }

    public function quote($string, $parameter_type = PDO::PARAM_STR): string|false
    {
        throw DatabaseNotConfiguredException::forMethod(__METHOD__);
    }

}