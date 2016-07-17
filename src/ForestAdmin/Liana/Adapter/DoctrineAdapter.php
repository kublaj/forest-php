<?php

namespace ForestAdmin\Liana\Adapter;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use ForestAdmin\Liana\Api\DataFilter;
use ForestAdmin\Liana\Api\ResourceFilter;
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
    public function listResources($filter)
    {
        $alias = 'resource';
        $collection = $this->getThisCollection();

        $queryBuilder = $this->getRepository()
            ->createQueryBuilder($alias);

        // First, count the total number of resources without filter
        $countQueryBuilder = clone $queryBuilder;
        $totalNumberOfRows = $countQueryBuilder
            ->select($countQueryBuilder->expr()->count($alias . '.' . $collection->getIdentifier()))
            ->getQuery()
            ->getSingleScalarResult();

        // Then, build the filter on resources
        $this->filterQueryBuilder($queryBuilder, $filter, $collection, $alias);

        // Finally, select all fields
        $queryBuilder->select($alias);

        $returnedResources = $this->loadResourcesFromQueryBuilder($queryBuilder, $this->getThisCollection());

        return Resource::formatResourcesJsonApi($returnedResources, $totalNumberOfRows);
    }

    /**
     * @param mixed $recordId
     * @param string $associationName
     * @param ResourceFilter $filter
     * @return object[] The hasMany resources with one relationships and a link to their many relationships
     * @throws AssociationNotFoundException
     * @throws CollectionNotFoundException
     * @throws RelationshipNotFoundException
     */
    public function getHasMany($recordId, $associationName, $filter)
    {
        if (!$this->hasIdentifier()) {
            return null;
        }

        try {
            $associatedCollection = $this->findCollection($associationName);
        } catch (CollectionNotFoundException $exc) {
            throw new AssociationNotFoundException($associationName);
        }

        $alias = 'resource';

        $associationRepository = $this->getEntityManager()
            ->getRepository($associatedCollection->getEntityClassName());

        $resourceQueryBuilder = $associationRepository
            ->createQueryBuilder($alias);

        $countQueryBuilder = clone $resourceQueryBuilder;
        $identifier = $associatedCollection->getIdentifier();
        $totalNumberOfRows = $countQueryBuilder
            ->select($countQueryBuilder->expr()->count($alias . '.' . $identifier))
            ->getQuery()
            ->getSingleScalarResult();

        $this->buildQueryToMany($resourceQueryBuilder, $associatedCollection, $recordId, $alias);
        $this->filterQueryBuilder($resourceQueryBuilder, $filter, $associatedCollection, $alias);

        $resourceQueryBuilder->select($alias);

        $returnedResources = $this->loadResourcesFromQueryBuilder($resourceQueryBuilder, $associatedCollection);

        return Resource::formatResourcesJsonApi($returnedResources, $totalNumberOfRows);
    }

    /**
     * @param array $postData
     * @return int The recordId of the created resource
     */
    public function createResource($postData)
    {
        $attributes = $postData['data']['attributes'];

        $collection = $this->getThisCollection();
        $entityName = $collection->getEntityClassName();
        $entity = new $entityName;

        foreach ($attributes as $property => $v) {
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
        $attributes = $postData['data']['attributes'];

        $collection = $this->getThisCollection();
        $entityName = $collection->getEntityClassName();
        $entity = new $entityName;

        $queryBuilder = $this->getRepository()->createQueryBuilder('up');
        $queryBuilder
            ->update($entityName, 'up')
            ->where($queryBuilder->expr()->eq('up.' . $collection->getIdentifier(), ':id'));

        foreach ($attributes as $property => $v) {
            if (property_exists($entity, $property)) {
                $queryBuilder->set('up.' . $property, ':' . $property);
            }
        }

        $query = $queryBuilder->getQuery();
        $query->setParameter('id', $recordId);
        foreach ($attributes as $property => $v) {
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

    /**
     * @param $resourceQueryBuilder
     * @param $collection
     * @return array
     * @throws CollectionNotFoundException
     */
    protected function loadResourcesFromQueryBuilder($resourceQueryBuilder, $collection)
    {
        $resources = $resourceQueryBuilder
            ->getQuery()
            ->getResult(\Doctrine\ORM\Query::HYDRATE_ARRAY);

        $returnedResources = array();

        if ($resources) {
            foreach ($resources as $resource) {
                $returnedResource = new ForestResource(
                    $collection,
                    $this->formatResource($resource)
                );
                $resourceId = $returnedResource->getId();
                $associatedRepository = $this->getEntityManager()->getRepository($collection->getEntityClassName());

                $relationships = $collection->getRelationships();

                if (count($relationships)) {
                    foreach ($relationships as $relationName => $field) {
                        $tableReference = $field->getReferencedTable();
                        if (!$tableReference) {
                            continue; // should not happen
                        }

                        $relatedCollection = $this->findCollection($tableReference);

                        if ($field->isTypeToMany()) {
                            $returnedResource->addRelationship($relatedCollection->getName());
                        } else {
                            $queryBuilder =
                                $associatedRepository
                                    ->createQueryBuilder('resource')
                                    ->andWhere('resource.' . $collection->getIdentifier() . ' = :identifier')
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

        return $returnedResources;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param ResourceFilter $filter
     * @param ForestCollection $collection
     * @param string $alias
     */
    public function filterQueryBuilder($queryBuilder, $filter, $collection, $alias)
    {
        if ($filter->hasSearch()) {
            $nested = $queryBuilder->expr()->orX();
            $searchValue = $queryBuilder->expr()->literal($filter->getSearch());

            foreach ($collection->getFields() as $field) {
                $fieldName = $alias . '.' . $field->getField();

                if ($fieldName == $alias . '.' . $collection->getIdentifier() || $field->getType() == 'String') {
                    $nested->add($queryBuilder->expr()->eq($fieldName, $searchValue));
                }
            }

            $queryBuilder->andWhere($nested);
        }

        if ($filter->hasFilters()) {
            foreach ($filter->getFilters() as $f) {
                /** @var DataFilter $f */
                $fieldName = $alias . '.' . $f->getFieldName();
                $filterValue = $queryBuilder->expr()->literal($f->getFilterString());

                if ($f->isDifferent()) {
                    $queryBuilder->andWhere($queryBuilder->expr()->neq($fieldName, $filterValue));
                } elseif ($f->isGreaterThan()) {
                    $queryBuilder->andWhere($queryBuilder->expr()->gt($fieldName, $filterValue));
                } elseif ($f->isLowerThan()) {
                    $queryBuilder->andWhere($queryBuilder->expr()->lt($fieldName, $filterValue));
                } elseif ($f->isContains() || $f->isStartsBy() || $f->isEndsBy()) {
                    $filterValue = $f->getFilterString();
                    if ($f->isContains() || $f->isStartsBy()) {
                        $filterValue = $filterValue . '%';
                    }
                    if ($f->isContains() || $f->isEndsBy()) {
                        $filterValue = '%' . $filterValue;
                    }
                    $filterValue = $queryBuilder->expr()->literal($filterValue);
                    $queryBuilder->andWhere($queryBuilder->expr()->like($fieldName, $filterValue));
                } elseif ($f->isPresent()) {
                    $queryBuilder->andWhere($queryBuilder->expr()->isNotNull($fieldName));
                } elseif ($f->isBlank()) {
                    $nested = $queryBuilder->expr()->orX(
                        $queryBuilder->expr()->isNull($fieldName),
                        $queryBuilder->expr()->eq($fieldName, $queryBuilder->expr()->literal(''))
                    );
                    $queryBuilder->andWhere($nested);
                } else {
                    $queryBuilder->andWhere($queryBuilder->expr()->eq($fieldName, $filterValue));
                }
            }
        }

        if ($filter->hasSortBy()) {
            $queryBuilder->addOrderBy($alias . '.' . $filter->getSortBy(), $filter->getSortOrder());
        }

        if ($filter->hasPageSize()) {
            $queryBuilder->setMaxResults($filter->getPageSize());

            if ($filter->hasPageNumber()) {
                $offset = $filter->getPageSize() * ($filter->getPageNumber() - 1);
                $queryBuilder->setFirstResult($offset);
            }
        }
    }

    /**
     * @param QueryBuilder $resourceQueryBuilder
     * @param ForestCollection $associatedCollection
     * @param string $recordId
     * @param string $alias
     */
    public function buildQueryToMany($resourceQueryBuilder, $associatedCollection, $recordId, $alias)
    {
        $relationship = $this->getThisCollection()->getRelationship($associatedCollection->getName());

        if($pivot = $relationship->getPivot()) {
            // if relation is many to many
            $resourceQueryBuilder
                ->join($pivot->getIntermediaryTableName(), 'pivot')
                ->andWhere('pivot.' . $pivot->getSourceIdentifier() . ' = :identifier')
                ->setParameter('identifier', $recordId);
        } else {
            // else it is one to many
            $modelIdentifier = $associatedCollection->getRelationship($this->getThisCollection()->getName())->getField();

            $resourceQueryBuilder
                ->andWhere($alias . '.' . $modelIdentifier . ' = :identifier')
                ->setParameter('identifier', $recordId);
        }
    }
}