<?php

namespace ForestAdmin\Liana\Analyzer;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\MappingException;
use ForestAdmin\Liana\Model\Collection as ForestCollection;
use ForestAdmin\Liana\Model\Field as ForestField;

class DoctrineAnalyzer implements OrmAnalyzer
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
        $this->resetManyToManyAssociations();
    }

    /**
     * @return ForestCollection[]
     */
    public function analyze()
    {
        $this->initConnection();

        return $this->getCollections();
    }

    /**
     * @param EntityManager $em
     * @return $this
     */
    public function setEntityManager($em)
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
     * @return $this
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
     * @return ForestCollection[]
     */
    public function getCollections()
    {
        $this->resetManyToManyAssociations();

        $ret = array();

        foreach ($this->getMetadata() as $classMetadata) {
            $ret[$classMetadata->getName()] = new ForestCollection(
                $classMetadata->getTableName(),
                null,//$this->getRepositoryObject($classMetadata),
                $this->getCollectionFields($classMetadata)
            );
        }

        $ret = array_merge($ret, $this->getManyToManyAssociations());

        return $ret;
    }

    /**
     * @param ClassMetadata $classMetadata
     * @return ForestField[]
     */
    public function getCollectionFields(ClassMetadata $classMetadata)
    {
        return array_merge(
            $this->getTableFields($classMetadata),
            $this->getAssociationFields($classMetadata)
        );
    }

    /**
     * @param ClassMetadata $classMetadata
     * @return ForestField[]
     */
    public function getTableFields(ClassMetadata $classMetadata)
    {
        $fields = array();

        foreach ($classMetadata->getFieldNames() as $fieldName) {
            $field = $this->createField($fieldName, $classMetadata);
            $fields[] = $field;
        }

        return $fields;
    }

    /**
     * @param ClassMetadata $classMetadata
     * @return ForestField[]
     */
    public function getAssociationFields(ClassMetadata $classMetadata)
    {
        $fields = array();

        if (count($classMetadata->getAssociationMappings())) {
            foreach ($classMetadata->getAssociationMappings() as $associationMapping) {
                $association = $this->getFieldForAssociation($associationMapping, $classMetadata);
                $fields = array_merge($fields, $association);
            }
        }

        return $fields;
    }

    /**
     * @param array $sourceAssociation AssociationMapping array (flat)
     * @param ClassMetadata $sourceClassMetadata
     * @return ForestField[]|array array of Fields or empty
     * @throws \Doctrine\ORM\Mapping\MappingException
     */
    protected function getFieldForAssociation($sourceAssociation, $sourceClassMetadata)
    {
        $returnedAssociation = array();
        $field = null;

        if (array_key_exists('joinColumns', $sourceAssociation)) {
            // One-To-One or Many-To-One
            $field = $this->getFieldForToOneAssociation($sourceAssociation);
        } elseif (array_key_exists('joinTable', $sourceAssociation) && $sourceAssociation['joinTable']) {
            // Many-To-Many
            $this->createIntermediaryTable($sourceAssociation, $sourceClassMetadata);
        } else {
            // One-To-Many
            $field = $this->getFieldForOneToManyAssociation($sourceAssociation);
        }

        if ($field) {
            array_push($returnedAssociation, $field);
        }

        return $returnedAssociation;
    }

    /**
     * Create a schema array for one-to-one and many-to-one associations
     * 
     * @param $sourceAssociation
     * @return ForestField
     */
    protected function getFieldForToOneAssociation($sourceAssociation)
    {
        $targetClassMetadata = $this->getClassMetadata($sourceAssociation['targetEntity']);
        $joinedColumn = reset($sourceAssociation['joinColumns']);
        
        $type = 'Number'; //$this->getTypeForAssociation($sourceAssociation); //not sure, to test
    
        $columnName = $joinedColumn['name'];
    
        $inverseOf = $sourceAssociation['inversedBy'];
    
        $foreignColumnName = $joinedColumn['referencedColumnName'];
        $foreignTableName = $targetClassMetadata->getTableName();
        $reference = $foreignTableName . '.' . $foreignColumnName;

        return new ForestField($columnName, $type, $reference, $inverseOf);
    }

    /**
     * Create a schema array for one-to-many associations
     * 
     * @param array $sourceAssociation
     * @return ForestField|null
     * @throws \Doctrine\ORM\Mapping\MappingException
     */
    protected function getFieldForOneToManyAssociation($sourceAssociation)
    {
        $targetClassMetadata = $this->getClassMetadata($sourceAssociation['targetEntity']);
        $mappedBy = $sourceAssociation['mappedBy'];
        
        try {
            $targetAssociation = $targetClassMetadata->getAssociationMapping($mappedBy);
        } catch(MappingException $exc) {
            /**
             * TODO:  What do we do when mapping is wrong for Association?
             */
            //Error : mapping does not exist => do not register
            return null;
        }
        
        if (array_key_exists('joinTable', $targetAssociation) && $targetAssociation['joinTable']) {
            // Many-To-Many
            $this->createIntermediaryTable($targetAssociation, $targetClassMetadata);
            return null;
        }

        $joinedColumn = reset($targetAssociation['joinColumns']);

        $type = $this->getTypeForAssociation($sourceAssociation);

        $columnName = $sourceAssociation['mappedBy'];

        $inverseOf = $targetAssociation['inversedBy'];

        $foreignColumnName = $joinedColumn['name'];
        $foreignTableName = $targetClassMetadata->getTableName();
        $reference = $foreignTableName . '.' . $foreignColumnName;

        return new ForestField($columnName, $type, $reference, $inverseOf);
    }

    /**
     * @param $sourceAssociation
     * @param ClassMetadata $sourceClassMetadata
     * @return bool
     */
    protected function createIntermediaryTable($sourceAssociation, $sourceClassMetadata)
    {
        $intermediaryTableName = $sourceAssociation['joinTable']['name'];

        if ($this->hasManyToManyAssociation($intermediaryTableName)) {
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

        $column1 = new ForestField($targetColumnName, $type, $targetReference);
        $column2 = new ForestField($sourceColumnName, $type, $sourceReference);
        $intermediaryTableSchema = $this->getManyToManyCollection($intermediaryTableName, $column1, $column2);

        $this->addManyToManyAssociation($intermediaryTableName, $intermediaryTableSchema);

        return true;
    }

    /**
     * @param string $fieldName
     * @param ClassMetadata $classMetadata
     * @return ForestField
     */
    protected function createField($fieldName, ClassMetadata $classMetadata)
    {
        // in doctrine, field=class property name, column=column name
        // TODO in Forest, does field equal doctrine field or doctrine column?
        return new ForestField(
            $classMetadata->getColumnName($fieldName),
            $classMetadata->getFieldMapping($fieldName)['type']
        );
    }

    /**
     * @param string $intermediaryTableName
     * @param ForestField $field1
     * @param ForestField $field2
     * @return ForestCollection
     */
    protected function getManyToManyCollection($intermediaryTableName, $field1, $field2)
    {
        return new ForestCollection(
            $intermediaryTableName,
            null,
            array($field1, $field2)
        );
    }

    /**
     * @param $associationMapping
     * @return string
     */
    protected function getTypeForAssociation($associationMapping)
    {
        if ($associationMapping['isOwningSide']) {
            return 'Number';
        }

        return array('Number');
    }

    /**
     *
     */
    protected function resetManyToManyAssociations()
    {
        $this->manyToManyAssociations = array();
    }

    /**
     * @return ForestCollection[]
     */
    protected function getManyToManyAssociations()
    {
        return $this->manyToManyAssociations;
    }

    /**
     * @param string $tableName
     * @param ForestCollection $manyToManyCollection
     */
    protected function addManyToManyAssociation($tableName, $manyToManyCollection)
    {
        $this->manyToManyAssociations[$tableName] = $manyToManyCollection;
    }

    /**
     * @param string $tableName
     * @return bool
     */
    protected function hasManyToManyAssociation($tableName)
    {
        return array_key_exists($tableName, $this->manyToManyAssociations);
    }

    /**
     * @param ClassMetadata $classMetadata
     * @return string
     */
    protected function getRepositoryObject(ClassMetadata $classMetadata)
    {
        if($classMetadata->rootEntityName && $this->getEntityManager()) {
            //return new $classMetadata->rootEntityName($this->getEntityManager(), $classMetadata);
            return $classMetadata->customRepositoryClassName;
        }
    }
}