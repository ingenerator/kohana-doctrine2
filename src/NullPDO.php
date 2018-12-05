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

    public function setAttribute($attribute, $value)
    {
        // No-op
    }

    public function getAttribute($attribute)
    {
        switch ($attribute) {
            case static::ATTR_DRIVER_NAME:
                return $this->driver;
        }

        throw DatabaseNotConfiguredException::forMethod(__METHOD__);
    }

    public function prepare($statement, $driver_options = [])
    {
        throw DatabaseNotConfiguredException::forMethod(__METHOD__);
    }

    public function beginTransaction()
    {
        throw DatabaseNotConfiguredException::forMethod(__METHOD__);
    }

    public function commit()
    {
        throw DatabaseNotConfiguredException::forMethod(__METHOD__);
    }

    public function rollBack()
    {
        throw DatabaseNotConfiguredException::forMethod(__METHOD__);
    }

    public function inTransaction()
    {
        throw DatabaseNotConfiguredException::forMethod(__METHOD__);
    }

    public function exec($statement)
    {
        throw DatabaseNotConfiguredException::forMethod(__METHOD__);
    }

    public function query($statement, $mode = PDO::ATTR_DEFAULT_FETCH_MODE, $arg3 = NULL, array $ctorargs = [])
    {
        throw DatabaseNotConfiguredException::forMethod(__METHOD__);
    }

    public function lastInsertId($name = NULL)
    {
        throw DatabaseNotConfiguredException::forMethod(__METHOD__);
    }

    public function errorCode()
    {
        throw DatabaseNotConfiguredException::forMethod(__METHOD__);
    }

    public function errorInfo()
    {
        throw DatabaseNotConfiguredException::forMethod(__METHOD__);
    }

    public function quote($string, $parameter_type = PDO::PARAM_STR)
    {
        throw DatabaseNotConfiguredException::forMethod(__METHOD__);
    }

}