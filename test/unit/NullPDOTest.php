<?php

namespace test\unit\Ingenerator\KohanaDoctrine;


use Ingenerator\KohanaDoctrine\DatabaseNotConfiguredException;
use Ingenerator\KohanaDoctrine\NullPDO;
use InvalidArgumentException;
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

    public function test_it_throws_if_driver_name_not_supported()
    {
        $this->expectException(InvalidArgumentException::class);
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

    public function test_it_throws_on_access_to_unexpected_get_attribute_call()
    {
        $this->expectException(DatabaseNotConfiguredException::class);
        $this->newSubject()->getAttribute(\PDO::ATTR_SERVER_VERSION);
    }

    public function provider_throwing_method_calls()
    {
        // Dynamic get all underlying PDO methods with dummy arguments
        $cases = [];
        $refl  = new \ReflectionClass(\PDO::class);
        foreach ($refl->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            $param_count = $method->getNumberOfParameters();
            $args        = $param_count ? \array_fill(0, $param_count, NULL) : [];

            $cases[$method->getName()] = [$method->getName(), $args];
        }

        $cases['query'] = ['query', [""]];

        // These methods don't throw the exception, anything else should
        unset($cases['__construct']);
        unset($cases['__wakeup']);
        unset($cases['__sleep']);
        unset($cases['getAttribute']);
        unset($cases['getAvailableDrivers']);
        unset($cases['setAttribute']);

        return \array_values($cases);
    }

    /**
     * @dataProvider provider_throwing_method_calls
     */
    public function test_it_throws_database_not_configured_on_any_call_to_pdo_method($method, $args)
    {
        $subject = $this->newSubject();
        $this->expectException(DatabaseNotConfiguredException::class);
        \call_user_func_array([$subject, $method], $args);
    }

    protected function newSubject()
    {
        return new NullPDO($this->driver);
    }
}
