<?php

namespace test\unit\Ingenerator\KohanaDoctrine\Dependency;


use Ingenerator\KohanaDoctrine\Dependency\ConnectionConfigProvider;
use Ingenerator\KohanaDoctrine\NullPDO;
use PHPUnit\Framework\TestCase;

class ConnectionConfigProviderTest extends TestCase
{
    protected $config = [];

    public function test_it_is_initialisable()
    {
        $this->assertInstanceOf(ConnectionConfigProvider::class, $this->newSubject());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function test_it_throws_if_legacy_config_attribute_specifies_database_other_than_mysql()
    {
        $this->config['type'] = 'Postgres';
        $this->newSubject()->getConnection();
    }

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
        $this->assertArraySubset(['driver' => 'pdo_mysql', 'charset' => 'utf8'], $connection);
    }

    public function test_it_parses_config_structure_to_doctrine_connection_config_if_host_configured()
    {
        $this->config = [
            'type'       => 'MySQL',
            'connection' => [
                'hostname' => 'localhost',
                'database' => 'ourdatabase',
                'username' => 'anyone',
                'password' => 'anything',
            ],
            'charset'    => 'cp1212',
        ];
        $this->assertSame(
            [
                'driver'   => 'pdo_mysql',
                'host'     => 'localhost',
                'user'     => 'anyone',
                'password' => 'anything',
                'dbname'   => 'ourdatabase',
                'charset'  => 'cp1212',
            ],
            $this->newSubject()->getConnection()
        );
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
        $this->assertEquals(
            [
                'driver'  => 'pdo_mysql',
                'pdo'     => new NullPDO('pdo_mysql'),
                'charset' => 'cp1212',
            ],
            $this->newSubject()->getConnection()
        );
    }


    /**
     * @return ConnectionConfigProvider
     */
    protected function newSubject()
    {
        return new ConnectionConfigProvider($this->config);
    }

}