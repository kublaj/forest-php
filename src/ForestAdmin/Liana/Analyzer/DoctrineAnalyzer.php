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
        $ret = array();

        foreach ($this->getMetadata() as $classMetadata) {
            $tablesAndFields = array();
            $tablesAndFields['name'] = $classMetadata->getTableName();
            $tablesAndFields['fields'] = $this->getTableFieldsAndAssociations($classMetadata);
            $tablesAndFields['actions'] = array();

            $ret[$classMetadata->getName()] = $tablesAndFields;
        }

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
            $field = $this->getSchemaForColumn($fieldName, $classMetadata);
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
        $lastClassControlled = '';

        if(count($classMetadata->getAssociationMappings())) {
            foreach($classMetadata->getAssociationMappings() as $associationMapping) {
                if($lastClassControlled != $classMetadata->getName()) {
                    $lastClassControlled = $classMetadata->getName();
                }

                $association = $this->getSchemaForAssociation($associationMapping, $classMetadata);
                if(is_array($association) && count($association)) {
                    $fields = array_merge($fields, $association);
                }
            }
        }

        return $fields;
    }

    /**
     * @param array $sourceAssociation AssociationMapping array (flat array)
     * @param ClassMetadata $sourceClassMetadata
     * @return array|null
     * @throws \Doctrine\ORM\Mapping\MappingException
     */
    protected function getSchemaForAssociation($sourceAssociation, $sourceClassMetadata)
    {
        $returnedAssociation = array();

        $targetClassMetadata = $this->getClassMetadata($sourceAssociation['targetEntity']);
        $inverseOf = null;
        
        if(array_key_exists('joinColumns', $sourceAssociation)) {
            // OneToOne or ManyToOne
            $joinedColumn = reset($sourceAssociation['joinColumns']);
            $type = 'Number'; //$this->getTypeForAssociation($sourceAssociation); //not sure, to test
            if(!is_null($sourceAssociation['inversedBy'])) {
                $inverseOf = $sourceAssociation['fieldName'].'.'.$sourceAssociation['inversedBy'];
            }
        } elseif(array_key_exists('joinTable', $sourceAssociation)) {
            // ManyToMany
            // TODO move to a separate array
            return false;
            /*
            $inverseJoinColumn = reset($sourceAssociation['joinTable']['inverseJoinColumns']);
            $inverseOf = $sourceAssociation['fieldName'].'.'.$inverseJoinColumn['name'];
            $joinColumn = reset($sourceAssociation['joinTable']['joinColumns']);
            $joinedColumn = $joinColumn['name'];
            $type = '[Number]';
            */
        } else {
            // OneToMany
            $targetAssociation = $targetClassMetadata->getAssociationMapping($sourceAssociation['mappedBy']);
            $joinedColumn = reset($targetAssociation['joinColumns']);
            $type = '[Number]';
        }

        $columnName = $joinedColumn['name'];
        $foreignColumnName = $joinedColumn['referencedColumnName'];
        $foreignTableName = $targetClassMetadata->getTableName();

        $returnedAssociation[] = array(
            'field' => $columnName,
            'type' => $type,
            'reference' => $foreignTableName . '.' . $foreignColumnName,
            'inverseOf' => $inverseOf,
        );

        return $returnedAssociation;
    }

    /**
     * @param string $fieldName
     * @param ClassMetadata $column
     * @return array
     */
    protected function getSchemaForColumn($fieldName, ClassMetadata $classMetadata)
    {
        // in doctrine, field=class property name, column=column name
        // TODO in Forest, does field equal doctrine field or doctrine column? 
        return array(
            'field' => $classMetadata->getColumnName($fieldName),
            'type' => $this->getTypeFor($classMetadata->getFieldMapping($fieldName)['type']),
            'reference' => null,
        );
    }

    /**
     * TODO review
     * @param $associationMapping
     * @return string
     */
    protected function getTypeForAssociation($associationMapping)
    {
        if($associationMapping['isOwningSide']) {
            return 'Number';
        }

        return '[Number]';
    }

    protected function getTableForeignKeys(Table $table)
    {
        $foreignKeys = array();

        if (count($table->getForeignKeys())) {
            foreach ($table->getForeignKeys() as $fk) {
                $localColumns = $fk->getLocalColumns();
                $localColumn = reset($localColumns);
                $foreignKeys[$localColumn] = $fk;
            }
        }

        return $foreignKeys;
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