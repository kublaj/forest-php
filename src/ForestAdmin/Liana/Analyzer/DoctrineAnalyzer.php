<?php

namespace ForestAdmin\Liana\Analyzer;

use Doctrine\DBAL\Connection as Connection;
use Doctrine\DBAL\DriverManager as DriverManager;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;

class DoctrineAnalyzer
{
    /**
     * @var Connection
     */
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
        $params = array(
            'url' => $this->getDatabaseUrl(),
        );
        $connection = DriverManager::getConnection($params);

        if (!Type::hasType('json')) {
            Type::addType('json', Type::getType(Type::JSON_ARRAY));
        }

        $this->setConnection($connection);
    }

    /**
     * @return array
     */
    protected function getData()
    {
        $ret = array();
        $sm = $this->getConnection()->getSchemaManager();
        $tables = $sm->listTables();

        if (count($tables)) {
            $tablesAndFields = array();

            foreach ($tables as $table) {
                $tablesAndFields['name'] = $table->getName();
                $tablesAndFields['fields'] = $this->getTableFields($table);
                $tablesAndFields['actions'] = array();

                $ret[] = $tablesAndFields;
            }

        }

        return $ret;
    }

    /**
     * @return array
     */
    protected function getMeta()
    {
        $composerConfig = json_decode(file_get_contents(dirname(__FILE__) . '/../../../../composer.json'), true);

        return array(
            'liana' => array_key_exists('name', $composerConfig) ? $composerConfig['name'] : '',
            'liana-version' => array_key_exists('version', $composerConfig) ? $composerConfig['version'] : '',
        );
    }

    /**
     * @param Table $table
     * @return array
     */
    protected function getTableFields(Table $table)
    {
        $fields = array();

        foreach ($table->getColumns() as $column) {
            $field = $this->getSchemaForColumn($table, $column);

            $fields[] = $field;
        }

        return $fields;
    }

    /**
     * @param Table $table
     * @param Column $column
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    protected function getSchemaForColumn(Table $table, Column $column)
    {
        $foreignKeys = $this->getTableForeignKeys($table);

        if (in_array($column->getName(), array_keys($foreignKeys))) {
            return $this->getSchemaForAssociation($column, $foreignKeys[$column->getName()]);
        } else {
            return $this->getSchemaForSimpleColumn($column);
        }
    }

    protected function getSchemaForAssociation(Column $column, ForeignKeyConstraint $foreignKey)
    {
        $foreignTableName = $foreignKey->getForeignTableName();
        $foreignColumns = $foreignKey->getForeignColumns();
        $foreignColumnName = reset($foreignColumns);

        return array(
            'field' => $column->getName(),
            'type' => $this->getTypeFor($column->getType()),
            'reference' => $foreignTableName . '.' . $foreignColumnName,
            'inverseOf' => null,
        );
    }

    /**
     * @param Column $column
     * @return array
     */
    protected function getSchemaForSimpleColumn(Column $column)
    {
        return array(
            'field' => $column->getName(),
            'type' => $this->getTypeFor($column->getType()),
            'reference' => null,
        );
    }

    protected function getTableForeignKeys(Table $table)
    {
        $foreignKeys = array();

        if(count($table->getForeignKeys())) {
            foreach($table->getForeignKeys() as $fk) {
                $localColumns = $fk->getLocalColumns();
                $localColumn = reset($localColumns);
                $foreignKeys[$localColumn] = $fk;
            }
        }

        return $foreignKeys;
    }

    /**
     * @param Type $doctrineType
     * @return string
     */
    protected function getTypeFor(Type $doctrineType)
    {
        $type = $doctrineType->getName();

        switch ($type) {
            case Type::INTEGER:
            case Type::SMALLINT:
            case Type::FLOAT:
            case Type::DECIMAL:
                return 'Number';
            case Type::STRING:
            case Type::TEXT:
                return 'String';
            case Type::BOOLEAN:
                return 'Boolean';
            case Type::DATE:
            case Type::DATETIME:
            case Type::DATETIMETZ:
                return 'Date';
        }

        return $type;
    }
}