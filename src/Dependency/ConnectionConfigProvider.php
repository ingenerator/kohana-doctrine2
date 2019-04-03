<?php

namespace Ingenerator\KohanaDoctrine\Dependency;


use Ingenerator\KohanaDoctrine\NullPDO;

/**
 * Maps database config (in same structure as for legacy Kohana database components) to a doctrine config
 *
 * Provides a NullPDO if there is no database hostname configured, this allows use of Doctrine tooling like
 * the schema validator and proxy generator without having a database available e.g. during build steps
 */
class ConnectionConfigProvider
{

    /**
     * @var array
     */
    protected $config;

    /**
     * 
     * @param array $config e.g. the connection group from the database connection config.
     *                      [NB] it is not expected to be valid for this to be empty at runtime, but allowing a null
     *                      value allows us to create an instance in development / test environments without full
     *                      config.
     */
    public function __construct(array $config = NULL)
    {
        $this->config = \array_merge(
            [
                'type'       => 'MySQL',
                'connection' => [
                    'hostname' => NULL,
                    'database' => NULL,
                    'username' => NULL,
                    'password' => NULL,
                ],
                'charset'    => 'utf8',
            ],
            $config ?: []
        );
    }


    /**
     * Convert the config to a doctrine connection array, including a NullPDO if there's no DB
     *
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    public function getConnection()
    {
        if ($this->config['type'] !== 'MySQL') {
            throw new \InvalidArgumentException(
                __CLASS__.' only supports database type of MySQL, got '.$this->config['type']
            );
        }

        if ($this->config['connection']['hostname'] === NULL) {
            return [
                'driver'  => 'pdo_mysql',
                'pdo'     => new NullPDO('pdo_mysql'),
                'charset' => $this->config['charset'],
            ];
        }

        return [
            'driver'   => 'pdo_mysql',
            'host'     => $this->config['connection']['hostname'],
            'user'     => $this->config['connection']['username'],
            'password' => $this->config['connection']['password'],
            'dbname'   => $this->config['connection']['database'],
            'charset'  => $this->config['charset'],
        ];

    }
}