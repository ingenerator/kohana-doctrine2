<?php

namespace test\unit\Ingenerator\KohanaDoctrine\Dependency;


use Ingenerator\KohanaDoctrine\Dependency\ConnectionConfigProvider;
use Ingenerator\KohanaDoctrine\FakeMysqlDriver;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use function array_intersect_key;

class ConnectionConfigProviderTest extends TestCase
{
    protected $config = [];

    public function test_it_has_sane_defaults_for_database_type_mysql_and_charset_utf8()
    {
        $this->config = [
            'connection' => [
                'hostname' => 'localhost',
                'database' => 'ourdatabase',
                'username' => 'anyone',
                'password' => 'anything',
            ],
        ];
        $connection   = $this->newSubject()->getConnection();
        $this->assertSame(
            ['driver' => 'pdo_mysql', 'charset' => 'utf8'],
            array_intersect_key($connection, array_flip(['driver', 'pdo_mysql', 'charset']))
        );
    }

    public function test_it_has_sane_defaults_for_timeout()
    {
        $this->config = [
            'connection' => [
                'hostname' => 'localhost',
                'database' => 'ourdatabase',
                'username' => 'anyone',
                'password' => 'anything',
            ],
        ];
        $connection   = $this->newSubject()->getConnection();
        $this->assertSame(5, $connection['driverOptions'][\PDO::ATTR_TIMEOUT]);
    }

    public function test_it_is_initialisable()
    {
        $this->assertInstanceOf(ConnectionConfigProvider::class, $this->newSubject());
    }

    public function test_it_parses_config_structure_and_returns_null_pdo_if_no_host_configured()
    {
        $this->config = [
            'type'       => 'MySQL',
            'connection' => [
                'hostname' => NULL,
                'database' => 'ourdatabase',
                'username' => 'anyone',
                'password' => 'anything',
            ],
            'charset'    => 'cp1212',
        ];
        $this->assertSame(
            [
                'driverClass' => FakeMysqlDriver::class,
                'charset'     => 'cp1212',
            ],
            $this->newSubject()->getConnection()
        );
    }

    public function test_it_parses_config_structure_to_doctrine_connection_config_if_host_configured()
    {
        $this->config = [
            'type'            => 'MySQL',
            'connection'      => [
                'hostname' => 'localhost',
                'database' => 'ourdatabase',
                'username' => 'anyone',
                'password' => 'anything',
            ],
            'charset'         => 'cp1212',
            'timeout_seconds' => 10,
        ];
        $this->assertSame(
            [
                'driver'        => 'pdo_mysql',
                'host'          => 'localhost',
                'user'          => 'anyone',
                'password'      => 'anything',
                'dbname'        => 'ourdatabase',
                'charset'       => 'cp1212',
                'driverOptions' => [
                    \PDO::ATTR_TIMEOUT => 10,
                ],
            ],
            $this->newSubject()->getConnection()
        );
    }

    public function test_it_throws_if_legacy_config_attribute_specifies_database_other_than_mysql()
    {
        $this->config['type'] = 'Postgres';
        $this->expectException(InvalidArgumentException::class);
        $this->newSubject()->getConnection();
    }

    /**
     * @return ConnectionConfigProvider
     */
    protected function newSubject()
    {
        return new ConnectionConfigProvider($this->config);
    }

}
