<?php

namespace ForestAdmin\Liana\Adapter;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr;
use ForestAdmin\Liana\Exception\AssociationNotFoundException;
use ForestAdmin\Liana\Exception\CollectionNotFoundException;
use ForestAdmin\Liana\Exception\RelationshipNotFoundException;
use ForestAdmin\Liana\Model\Collection as ForestCollection;
use ForestAdmin\Liana\Model\Field as ForestField;
use ForestAdmin\Liana\Model\Resource as ForestResource;
use ForestAdmin\Liana\Model\Resource;

class DoctrineAdapter implements QueryAdapter
{
    /**
     * @var ForestCollection[]
     */
    protected $collections;

    /**
     * @var ForestCollection
     */
    protected $thisCollection;

    /**
     * @var EntityRepository
     */
    protected $repository;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * DoctrineProxy constructor.
     * @param ForestCollection[] $collections
     * @param ForestCollection $entityCollection
     * @param EntityManager $entityManager
     * @param EntityRepository $repository
     */
    public function __construct($collections, $entityCollection, $entityManager, $repository)
    {
        $this
            ->setCollections($collections)
            ->setThisCollection($entityCollection)
            ->setEntityManager($entityManager)
            ->setRepository($repository);
    }

    /**
     * @param ForestCollection[] $collections
     * @return $this
     */
    public function setCollections($collections)
    {
        $this->collections = $collections;

        return $this;
    }

    /**
     * @return ForestCollection[]
     */
    public function getCollections()
    {
        return $this->collections;
    }

    /**
     * @param ForestCollection $collection
     * @return $this
     */
    public function setThisCollection($collection)
    {
        $this->thisCollection = $collection;

        return $this;
    }

    /**
     * @return ForestCollection
     */
    public function getThisCollection()
    {
        return $this->thisCollection;
    }

    /**
     * @param EntityManager $entityManager
     * @return $this
     */
    public function setEntityManager($entityManager)
    {
        $this->entityManager = $entityManager;

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
     * @param EntityRepository $repository
     * @return $this
     */
    public function setRepository($repository)
    {
        $this->repository = $repository;

        return $this;
    }

    /**
     * @return EntityRepository
     */
    public function getRepository()
    {
        return $this->repository;
    }

    /**
     * Find a resource by its identifier
     *
     * @param mixed $recordId
     * @return object|null
     * @throws CollectionNotFoundException
     */
    public function getResource($recordId)
    {
        $returnedResource = null;

        if (!$this->hasIdentifier()) {
            return null;
        }

        $resourceQueryBuilder = $this->getRepository()
            ->createQueryBuilder('resource')
            ->andWhere('resource.' . $this->getThisCollection()->getIdentifier() . ' = :identifier')
            ->setParameter('identifier', $recordId);

        $resources = $resourceQueryBuilder
            ->select('resource')
            ->getQuery()
            ->getResult(\Doctrine\ORM\Query::HYDRATE_ARRAY);

        if ($resources) {
            $resource = reset($resources);

            $returnedResource = new ForestResource(
                $this->getThisCollection(),
                $this->formatResource($resource)
            );

            $relationships = $this->getThisCollection()->getRelationships();

            if (count($relationships)) {
                foreach ($relationships as $tableReference => $field) {
                    /** @var ForestField $field */
                    $foreignCollection = $this->findCollection($tableReference);

                    if ($field->isTypeToMany()) {
                        $returnedResource->addRelationship($foreignCollection->getName());
                    } else {
                        $queryBuilder = clone $resourceQueryBuilder;
                        $queryBuilder
                            ->select('relation')
                            ->join($foreignCollection->getEntityClassName(), 'relation')
                            ->andWhere('relation.' . $field->getReferencedField() . ' = resource.' . $field->getField());

                        $foreignResources = $queryBuilder
                            ->getQuery()
                            ->getResult(\Doctrine\ORM\Query::HYDRATE_ARRAY);

                        if ($foreignResources) {
                            $foreignResource = reset($foreignResources);
                            $resourceToInclude = new ForestResource(
                                $foreignCollection,
                                $this->formatResource($foreignResource, $foreignCollection)
                            );
                            $resourceToInclude->setType($field->getField());
                            $returnedResource->includeResource($resourceToInclude);
                        }
                    }
                }
            }
        }

        return $returnedResource->formatJsonApi();
    }

    /**
     * Find all resources by filter
     * @param ResourceFilter $filter
     * @return array
     */
    public function getResources($filter)
    {

    }

    /**
     * @param mixed $recordId
     * @param string $associationName
     * @return object[] The hasMany resources with one relationships and a link to their many relationships
     * @throws AssociationNotFoundException
     * @throws CollectionNotFoundException
     * @throws RelationshipNotFoundException
     */
    public function getHasMany($recordId, $associationName)
    {
        if (!$this->hasIdentifier()) {
            return null;
        }

        try {
            $associatedCollection = $this->findCollection($associationName);
        } catch (CollectionNotFoundException $exc) {
            throw new AssociationNotFoundException($associationName);
        }

        $associationRepository = $this->getEntityManager()
            ->getRepository($associatedCollection->getEntityClassName());

        $resourceQueryBuilder = $associationRepository
            ->createQueryBuilder('resource');

        $modelIdentifier = $associatedCollection->getRelationship($this->getThisCollection()->getName())->getField();

        $resourceQueryBuilder
            ->select('resource')
            ->where('resource.' . $modelIdentifier . ' = :identifier')
            ->setParameter('identifier', $recordId);

        $resources = $resourceQueryBuilder
            ->getQuery()
            ->getResult(\Doctrine\ORM\Query::HYDRATE_ARRAY);

        $returnedResources = array();

        if ($resources) {
            foreach ($resources as $resource) {
                $returnedResource = new ForestResource(
                    $associatedCollection,
                    $this->formatResource($resource)
                );
                $resourceId = $returnedResource->getId();
                $associatedRepository = $this->getEntityManager()->getRepository($associatedCollection->getEntityClassName());

                $relationships = $associatedCollection->getRelationships();

                if (count($relationships)) {
                    foreach ($relationships as $relationName => $field) {
                        $tableReference = $field->getReferencedTable();
                        if(!$tableReference) {
                            continue; // should not happen
                        }

                        $relatedCollection = $this->findCollection($tableReference);

                        if ($field->isTypeToMany()) {
                            $returnedResource->addRelationship($relatedCollection->getName());
                        } else {
                            $queryBuilder =
                                $associatedRepository
                                    ->createQueryBuilder('resource')
                                    ->andWhere('resource.' . $associatedCollection->getIdentifier() . ' = :identifier')
                                    ->setParameter('identifier', $resourceId)
                                    ->select('relation')
                                    ->join($relatedCollection->getEntityClassName(), 'relation')
                                    ->andWhere('relation.' . $field->getReferencedField() . ' = resource.' . $field->getField());

                            $foreignResources = $queryBuilder
                                    ->getQuery()
                                    ->getResult(\Doctrine\ORM\Query::HYDRATE_ARRAY);

                            if ($foreignResources) {
                                $foreignResource = reset($foreignResources);
                                $resourceToInclude = new ForestResource(
                                    $relatedCollection,
                                    $this->formatResource($foreignResource, $relatedCollection)
                                );
                                $resourceToInclude->setType($field->getField());
                                $returnedResource->includeResource($resourceToInclude);
                            }
                        }
                    }
                }

                $returnedResources[] = $returnedResource;
            }
        }

        return Resource::formatResourcesJsonApi($returnedResources);
    }

    /**
     * @param array $postData
     * @return int The recordId of the created resource
     */
    public function createResource($postData)
    {
        if (!$postData) {
            return null;
        }

        $collection = $this->getThisCollection();
        $entityName = $collection->getEntityClassName();
        $entity = new $entityName;

        foreach ($postData as $property => $v) {
            $setter = 'set' . ucfirst($property);

            if (method_exists($entity, $setter)) {
                $field = $collection->getField($property);
                $entityName = $this->findRelatedEntityClassName($field);

                if ($entityName) {
                    // The field is actually a relation, so we need an entity. Relations are always of type Number.
                    $v = $this->getEntityManager()->getRepository($entityName)->find($v);

                    if (!$v) {
                        continue;
                    }
                }

                if ($field->getType() == 'Date') {
                    // Date parameters can only be set as DateTime objects
                    $v = new \DateTime($v);
                }

                $entity->$setter($v);
            }
        }

        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();

        $getter = 'get' . ucfirst($collection->getIdentifier());
        $savedId = $entity->$getter();

        return $savedId;
    }

    /**
     * @param mixed $recordId
     * @param array $postData
     * @return int The recordId of the updated resource
     */
    public function updateResource($recordId, $postData)
    {
        if (!$postData) {
            return null;
        }

        $collection = $this->getThisCollection();
        $entityName = $collection->getEntityClassName();
        $entity = new $entityName;

        $queryBuilder = $this->getRepository()->createQueryBuilder('up');
        $queryBuilder
            ->update($entityName, 'up')
            ->where($queryBuilder->expr()->eq('up.' . $collection->getIdentifier(), ':id'));

        foreach ($postData as $property => $v) {
            if (property_exists($entity, $property)) {
                $queryBuilder->set('up.' . $property, ':' . $property);
            }
        }

        $query = $queryBuilder->getQuery();
        $query->setParameter('id', $recordId);
        foreach ($postData as $property => $v) {
            if (property_exists($entity, $property)) {
                $fieldType = $collection->getField($property)->getType();
                if ($fieldType == 'Date') {
                    // workaround: Date parameters can be set only with DateTime objects
                    $v = new \DateTime($v);
                }
                $query->setParameter($property, $v);
            }
        }

        $query->execute();
        $this->getEntityManager()->flush();

        return $recordId;
    }

    /**
     * @param object $resource
     * @param ForestCollection|null
     * @return array
     */
    protected function formatResource($resource, $collection = null)
    {
        if (is_null($collection)) {
            $collection = $this->getThisCollection();
        }

        $ret = array();
        foreach ($collection->getFields() as $field) {
            /** @var ForestField $field */
            $key = $field->getField();

            if (!array_key_exists($key, $resource)) {
                // *toMany Relationship => skip
                continue;
            }

            $value = $this->getResourceFieldValue($resource, $field);

            $ret[$key] = $value;
        }
        return $ret;
    }

    /**
     * @param object $resource
     * @param ForestField $field
     * @return mixed
     */
    protected function getResourceFieldValue($resource, $field)
    {
        $f = $field->getField();

        $value = $resource[$f];

        if (is_a($value, '\DateTime') && $field->getType() == 'Date') {
            /**
             * @var \DateTime $value
             */
            return $value->format('c'); // ISO-8601, takes timezone into account
        }

        if (is_array($value)) {
            $value = json_encode($value);
        }

        if ($field->getType() == 'Boolean') {
            return $value ? true : false;
        }

        //default
        return $value;
    }

    /**
     * @return bool
     */
    protected function hasIdentifier()
    {
        return count($this->getThisCollection()->getIdentifier()) ? true : false;
    }

    /**
     * @param string $tableReference
     * @return ForestCollection|null
     * @throws CollectionNotFoundException
     */
    protected function findCollection($tableReference)
    {
        foreach ($this->getCollections() as $collection) {
            if ($collection->getName() == $tableReference) {
                return $collection;
            }
        }

        throw new CollectionNotFoundException($tableReference);
    }

    /**
     * @param ForestField $field
     * @return bool|string
     * @throws CollectionNotFoundException
     */
    protected function findRelatedEntityClassName($field)
    {
        $relationName = $field->getReferencedTable();

        if ($relationName) {
            foreach ($this->getCollections() as $collection) {
                if ($collection->getName() == $relationName) {
                    return $collection->getEntityClassName();
                }
            }

            throw new CollectionNotFoundException($relationName);
        }

        return false;
    }
}