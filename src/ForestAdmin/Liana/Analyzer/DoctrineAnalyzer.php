<?php

namespace ForestAdmin\Liana\Analyzer;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
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
                $this->getEntityClassName($classMetadata),
                $classMetadata->getIdentifier(),
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
                $association = $this->getFieldForAssociation($associationMapping);
                $fields = array_merge($fields, $association);
            }
        }

        return $fields;
    }

    /**
     * @param array $sourceAssociation AssociationMapping array (flat)
     * @return ForestField[]|array array of Fields or empty
     * @throws \Doctrine\ORM\Mapping\MappingException
     */
    protected function getFieldForAssociation($sourceAssociation)
    {
        $returnedAssociation = array();
        $field = null;

        if (array_key_exists('joinColumns', $sourceAssociation)) {
            // *-To-One
            $field = $this->getFieldForToOneAssociation($sourceAssociation);
        } else {
            // *-To-Many
            $field = $this->getFieldForToManyAssociation($sourceAssociation);
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

        if (!$joinedColumn) {
            // One-to-One referenced only in foreign table => do not create field
            return null;
        }

        $type = $this->getTypeForAssociation($sourceAssociation);

        $fieldName = $sourceAssociation['fieldName'];

        $inverseOf = $sourceAssociation['inversedBy'];

        $foreignTableName = $targetClassMetadata->getTableName();
        $foreignIdentifier = $targetClassMetadata->getIdentifier();
        //if(count($foreignIdentifier) > 1) die(__METHOD__.'::'.__LINE__.': '.$foreignTableName.' has more than one identifier : '.json_encode($foreignIdentifier));
        $foreignIdentifier = reset($foreignIdentifier);
        $reference = $foreignTableName . '.' . $foreignIdentifier;

        return new ForestField($fieldName, $type, $reference, $inverseOf);
    }

    /**
     * Create a schema array for *-to-many associations
     *
     * @param array $sourceAssociation
     * @return ForestField|null
     * @throws \Doctrine\ORM\Mapping\MappingException
     */
    protected function getFieldForToManyAssociation($sourceAssociation)
    {
        $targetClassMetadata = $this->getClassMetadata($sourceAssociation['targetEntity']);
        
        $type = $this->getTypeForAssociation($sourceAssociation);
        
        $fieldName = $sourceAssociation['fieldName'];

        $inverseOf = $sourceAssociation['inversedBy'];

        $foreignTableName = $targetClassMetadata->getTableName();
        $foreignIdentifier = $targetClassMetadata->getIdentifier();
        //if(count($foreignIdentifier) > 1) die(__METHOD__.'::'.__LINE__.': '.$foreignTableName.' has more than one identifier : '.json_encode($foreignIdentifier));
        $foreignIdentifier = reset($foreignIdentifier);
        $reference = $foreignTableName . '.' . $foreignIdentifier;

        return new ForestField($fieldName, $type, $reference, $inverseOf);
    }

    /**
     * @param string $fieldName
     * @param ClassMetadata $classMetadata
     * @return ForestField
     */
    protected function createField($fieldName, ClassMetadata $classMetadata)
    {
        return new ForestField(
            $fieldName,
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
        if($associationMapping['type'] & ClassMetadataInfo::TO_MANY) {
            return array('Number');
        }

        return 'Number';
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
    protected function getEntityClassName(ClassMetadata $classMetadata)
    {
        return $classMetadata->rootEntityName;
    }
}