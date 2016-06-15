<?php

namespace ForestAdmin\Liana\Analyzer;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;

class DoctrineAnalyzer
{
    protected $connection;

    /**
     * @var string
     */
    protected $database_url;

    /**
     * @var string
     */
    protected $database_name;

    /**
     * DoctrineAnalyzer constructor.
     */
    public function __construct()
    {
    }

    /**
     * @return array
     */
    public function analyze()
    {
        $this->initConnection();

        return array(
            'data' => $this->getData(),
            'meta' => $this->getMeta(),
        );
    }

    /**
     * @param string $dbName
     * @return $this
     */
    public function setDatabaseName($dbName)
    {
        $this->database_name = $dbName;
        
        return $this;
    }

    /**
     * @return string
     */
    public function getDatabaseName()
    {
        return $this->database_name;
    }

    /**
     * @param string $dbUrl
     * @return $this
     */
    public function setDatabaseUrl($dbUrl)
    {
        $this->database_url = $dbUrl;

        return $this;
    }

    /**
     * @return string
     */
    public function getDatabaseUrl()
    {
        return $this->database_url;
    }

    /**
     * @param Connection $connection
     * @return $this
     */
    public function setConnection(Connection $connection)
    {
        $this->connection = $connection;
        
        return $this;
    }

    public function getConnection()
    {
        return $this->connection;
    }

    protected function initConnection()
    {
        $url = $this->getDatabaseUrl();
        list($driverName, $url) = explode('://', $url);
        list($url, $dbName) = explode('/', $url);
        list($userpass, $hostport) = explode('@', $url);
        list($dbUser, $dbPassword) = explode(':', $userpass);
        list($dbHost, $dbPort) = explode(':', $hostport);
        switch($driverName) {
            case 'mysql':
            case 'sqlite':
            case 'pgsql':
            case 'oci':
            case 'sqlsrv':
                $driverName = 'pdo_'.$driverName;
                break;
        }
        $params = array(
            'driver' => $driverName,
            'username' => $dbUser,
            'password' => $dbPassword,
            'host' => $dbHost,
            'port' => $dbPort,
            'name' => $dbName,
        );

        $this->setConnection(DriverManager::getConnection($params));
    }

    /**
     * @return array
     */
    protected function getData()
    {
        return array();
    }

    /**
     * @return array
     */
    protected function getMeta()
    {
        $composerConfig = json_decode(file_get_contents(dirname(__FILE__).'/../../../../composer.json'), true);

        return array(
            'liana' => array_key_exists('name', $composerConfig) ? $composerConfig['name'] : '',
            'liana-version' => array_key_exists('version', $composerConfig) ? $composerConfig['version'] : '',
        );
    }
}