<?php

namespace test\unit\Ingenerator\KohanaDoctrine;


use Ingenerator\KohanaDoctrine\NullPDO;
use PDO;
use PHPUnit\Framework\TestCase;

class NullPDOTest extends TestCase
{

    /**
     * @var string
     */
    protected $driver = 'pdo_mysql';

    public function test_it_is_initialisable_pdo()
    {
        $subject = $this->newSubject();
        $this->assertInstanceOf(NullPDO::class, $subject);
        $this->assertInstanceOf(PDO::class, $subject);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function test_it_throws_if_driver_name_not_supported()
    {
        $this->driver = 'pdo_pgsql';
        $this->newSubject();
    }

    public function test_it_silently_accepts_set_attributes()
    {
        $this->newSubject()->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->assertTrue(TRUE);
    }

    public function test_it_returns_driver_name_attribute()
    {
        $this->assertSame('mysql', $this->newSubject()->getAttribute(\PDO::ATTR_DRIVER_NAME));
    }

    /**
     * @expectedException \Ingenerator\KohanaDoctrine\DatabaseNotConfiguredException
     */
    public function test_it_throws_on_access_to_unexpected_get_attribute_call()
    {
        $this->newSubject()->getAttribute(\PDO::ATTR_SERVER_VERSION);
    }

    public function provider_throwing_method_calls()
    {
        // Dynamic get all underlying PDO methods with dummy arguments
        $cases = [];
        $refl  = new \ReflectionClass(\PDO::class);
        foreach ($refl->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            $cases[$method->getName()] = [
                $method->getName(),
                array_fill(0, $method->getNumberOfParameters(), NULL),
            ];
        }

        // For some reason ->query gives the wrong parameter info in Reflection
        $cases['query'] = ['query', [NULL]];

        // These methods don't throw the exception, anything else should
        unset($cases['__construct']);
        unset($cases['__wakeup']);
        unset($cases['__sleep']);
        unset($cases['getAttribute']);
        unset($cases['getAvailableDrivers']);
        unset($cases['setAttribute']);

        return array_values($cases);
    }

    /**
     * @dataProvider provider_throwing_method_calls
     * @expectedException \Ingenerator\KohanaDoctrine\DatabaseNotConfiguredException
     */
    public function test_it_throws_database_not_configured_on_any_call_to_pdo_method($method, $args)
    {
        $subject = $this->newSubject();
        call_user_func_array([$subject, $method], $args);
    }

    protected function newSubject()
    {
        return new NullPDO($this->driver);
    }
}