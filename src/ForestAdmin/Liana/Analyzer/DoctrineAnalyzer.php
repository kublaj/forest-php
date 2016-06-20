<?php

namespace ForestAdmin\Liana\Analyzer;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\EntityManager;

class DoctrineAnalyzer
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var ClassMetadata[]
     */
    protected $metadata;

    /**
     * Temporary array to compile many-to-many associations into intermediary tables in analysis
     * @var array
     */
    protected $manyToManyAssociations;

    /**
     * DoctrineAnalyzer constructor.
     */
    public function __construct()
    {
        $this->manyToManyAssociations = array();
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
     * @param EntityManager $em
     * @return $this
     */
    public function setEntityManager(EntityManager $em)
    {
        $this->entityManager = $em;

        return $this;
    }

    /**
     * @return EntityManager
     */
    public function getEntityManager()
    {
        return $this->entityManager;
    }

    /**
     * @param ClassMetadata[] $data
     */
    public function setMetadata($data)
    {
        $this->metadata = array();

        foreach ($data as $cm) {
            $this->metadata[$cm->getName()] = $cm;
        }

        return $this;
    }

    /**
     * @return ClassMetadata[]
     */
    public function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * 
     */
    public function resetManyToManyAssociations()
    {
        $this->manyToManyAssociations = array();
    }

    /**
     * @return array
     */
    public function getManyToManyAssociations()
    {
        return $this->manyToManyAssociations;
    }

    /**
     * @param string $tableName
     * @param array $tableSchema from getTableSchema
     */
    public function addManyToManyAssociation($tableName, $tableSchema)
    {
        $this->manyToManyAssociations[$tableName] = $tableSchema;
    }

    /**
     * @param string $key
     * @return ClassMetadata|null
     */
    public function getClassMetadata($key)
    {
        if (array_key_exists($key, $this->metadata)) {
            return $this->metadata[$key];
        }

        return null;
    }

    protected function initConnection()
    {
        if (!$this->getMetadata()) {
            if ($this->getEntityManager()) {
                $this->setMetadata($this->getEntityManager()->getMetadataFactory()->getAllMetadata());
            }
        }
    }

    /**
     * @return array
     */
    public function getData()
    {
        $this->resetManyToManyAssociations();
        
        $ret = array();

        foreach ($this->getMetadata() as $classMetadata) {
            $ret[$classMetadata->getName()] = $this->getTableSchema(
                $classMetadata->getTableName(),
                $this->getTableFieldsAndAssociations($classMetadata)
            );
        }
        
        $ret = array_merge($ret, $this->getManyToManyAssociations());

        return $ret;
    }

    /**
     * @return array
     */
    public function getMeta()
    {
        $composerConfig = json_decode(file_get_contents(dirname(__FILE__) . '/../../../../composer.json'), true);

        return array(
            'liana' => array_key_exists('name', $composerConfig) ? $composerConfig['name'] : '',
            'liana-version' => array_key_exists('version', $composerConfig) ? $composerConfig['version'] : '',
        );
    }

    /**
     * @param ClassMetadata $classMetadata
     * @return array
     */
    public function getTableFieldsAndAssociations(ClassMetadata $classMetadata)
    {
        return array_merge(
            $this->getTableFields($classMetadata),
            $this->getAssociationFields($classMetadata)
        );
    }

    /**
     * @param ClassMetadata $classMetadata
     * @return array
     */
    public function getTableFields(ClassMetadata $classMetadata)
    {
        $fields = array();

        foreach ($classMetadata->getFieldNames() as $fieldName) {
            $field = $this->getSchemaForField($fieldName, $classMetadata);
            $fields[] = $field;
        }

        return $fields;
    }

    /**
     * @param ClassMetadata $classMetadata
     * @return array
     */
    public function getAssociationFields(ClassMetadata $classMetadata)
    {
        $fields = array();

        if (count($classMetadata->getAssociationMappings())) {
            foreach ($classMetadata->getAssociationMappings() as $associationMapping) {
                $association = $this->getSchemaForAssociation($associationMapping, $classMetadata);
                $fields = array_merge($fields, $association);
            }
        }

        return $fields;
    }

    /**
     * @param array $sourceAssociation AssociationMapping array (flat array)
     * @param ClassMetadata $sourceClassMetadata
     * @return array
     * @throws \Doctrine\ORM\Mapping\MappingException
     */
    protected function getSchemaForAssociation($sourceAssociation, $sourceClassMetadata)
    {
//        $returnedAssociation = array();

        $targetClassMetadata = $this->getClassMetadata($sourceAssociation['targetEntity']);

        if (array_key_exists('joinColumns', $sourceAssociation)) {
            // OneToOne or ManyToOne
            $joinedColumn = reset($sourceAssociation['joinColumns']);
            $type = 'Number'; //$this->getTypeForAssociation($sourceAssociation); //not sure, to test
            $inversedBy = null;
            if (!is_null($sourceAssociation['inversedBy'])) {
                $inversedBy = $sourceAssociation['fieldName'] . '.' . $sourceAssociation['inversedBy'];
            }

            $columnName = $joinedColumn['name'];
            $foreignColumnName = $joinedColumn['referencedColumnName'];
            $foreignTableName = $targetClassMetadata->getTableName();
            $reference = $foreignTableName . '.' . $foreignColumnName;

            return array(
                $this->getColumnSchema($columnName, $type, $reference, $inversedBy)
            );
        } elseif (array_key_exists('joinTable', $sourceAssociation) && $sourceAssociation['joinTable']) {
            // ManyToMany
            $this->createIntermediaryTable($sourceAssociation, $sourceClassMetadata);
            return array();
        } else {
            // OneToMany
            $targetAssociation = $targetClassMetadata->getAssociationMapping($sourceAssociation['mappedBy']);
            if (array_key_exists('joinTable', $targetAssociation) && $targetAssociation['joinTable']) {
                // ManyToMany?
                $this->createIntermediaryTable($targetAssociation, $sourceClassMetadata);
                return array();
            }
            $joinedColumn = reset($targetAssociation['joinColumns']);
            $type = '[Number]';
            $inversedBy = null;
            if (!is_null($sourceAssociation['inversedBy'])) {
                $inversedBy = $sourceAssociation['fieldName'] . '.' . $sourceAssociation['inversedBy'];
            }

            $columnName = $joinedColumn['name'];
            $foreignColumnName = $joinedColumn['referencedColumnName'];
            $foreignTableName = $targetClassMetadata->getTableName();
            $reference = $foreignTableName . '.' . $foreignColumnName;

            return array(
                $this->getColumnSchema($columnName, $type, $reference, $inversedBy)
            );
        }
//      return $returnedAssociation;
    }

    /**
     * @param $sourceAssociation
     * @param ClassMetadata $sourceClassMetadata
     * @return bool
     */
    protected function createIntermediaryTable($sourceAssociation, $sourceClassMetadata)
    {
        $intermediaryTableName = $sourceAssociation['joinTable']['name'];

        if (array_key_exists($intermediaryTableName, $this->manyToManyAssociations)) {
            return false;
        }

        $joinColumn = reset($sourceAssociation['joinTable']['joinColumns']);
        $sourceTableName = $sourceClassMetadata->getTableName();
        $sourceColumnName = $joinColumn['name'];
        $sourceReference = $sourceTableName . '.' . $joinColumn['referencedColumnName'];

        $type = 'Number';

        $targetClassMetadata = $this->getClassMetadata($sourceAssociation['targetEntity']);
        $targetJoinColumn = reset($sourceAssociation['joinTable']['inverseJoinColumns']);
        $targetTableName = $targetClassMetadata->getTableName();
        $targetColumnName = $targetJoinColumn['name'];
        $targetReference = $targetTableName . '.' . $targetJoinColumn['referencedColumnName'];

        //$inverseOf = null;

        $column1 = $this->getColumnSchema($targetColumnName, $type, $targetReference);
        $column2 = $this->getColumnSchema($sourceColumnName, $type, $sourceReference);
        $intermediaryTableSchema = $this->getIntermediaryTableSchema($intermediaryTableName, $column1, $column2);
        $this->addManyToManyAssociation($intermediaryTableName, $intermediaryTableSchema);
        
        return true;
    }

    /**
     * @param string $fieldName
     * @param ClassMetadata $classMetadata
     * @return array
     */
    protected function getSchemaForField($fieldName, ClassMetadata $classMetadata)
    {
        // in doctrine, field=class property name, column=column name
        // TODO in Forest, does field equal doctrine field or doctrine column?
        return $this->getColumnSchema(
            $classMetadata->getColumnName($fieldName),
            $this->getTypeFor($classMetadata->getFieldMapping($fieldName)['type'])
        );
    }

    /**
     * @param string $name
     * @param array $fields
     * @param array|null $actions
     * @return array
     */
    protected function getTableSchema($name, $fields, $actions = null)
    {
        $ret = array();
        $ret['name'] = $name;
        $ret['fields'] = $fields;
        $ret['actions'] = is_null($actions) ? array() : $actions;
        
        return $ret;
    }

    /**
     * @param string $field
     * @param string $type
     * @param string|null $reference
     * @param string|null $inverseOf
     * @param array|null $extra
     * @return array
     */
    protected function getColumnSchema($field, $type, $reference = null, $inverseOf = null, $extra = null)
    {
        $ret = array();
        $ret['field'] = $field;
        $ret['type'] = $type;
        $ret['reference'] = $reference;

        if (!is_null($inverseOf)) {
            $ret['inverseOf'] = $inverseOf;
        }

        if (!is_null($extra) && is_array($extra) && count($extra)) {
            foreach ($extra as $schemaFieldName => $schemaFieldValue) {
                $ret[$schemaFieldName] = $schemaFieldValue;
            }
        }

        return $ret;
    }

    /**
     * @param string $intermediaryTableName
     * @param array $column1                returned by getColumnSchema
     * @param array $column2                returned by getColumnSchema
     * @return array
     */
    protected function getIntermediaryTableSchema($intermediaryTableName, $column1, $column2)
    {
        return $this->getTableSchema(
            $intermediaryTableName, 
            array($column1, $column2)
        );
    }

    /**
     * TODO review
     * @param $associationMapping
     * @return string
     */
    protected function getTypeForAssociation($associationMapping)
    {
        if ($associationMapping['isOwningSide']) {
            return 'Number';
        }

        return '[Number]';
    }

    /**
     * @param string $doctrineType
     * @return string
     */
    protected function getTypeFor($doctrineType)
    {
        switch ($doctrineType) {
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

        return $doctrineType;
    }
}