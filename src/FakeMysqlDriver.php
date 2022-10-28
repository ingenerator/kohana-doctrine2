<?php

namespace Ingenerator\KohanaDoctrine;


use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\PDO\Connection;

class FakeMysqlDriver extends Driver\AbstractMySQLDriver
{

    public function connect(array $params)
    {
        return new Connection(new NullPDO('pdo_mysql'));
    }

    public function getName()
    {
        return 'pdo_mysql';
    }

}
