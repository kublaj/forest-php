<?php

namespace ForestAdmin\Liana\Adapter;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr;
use ForestAdmin\Liana\Model\Collection as ForestCollection;
use ForestAdmin\Liana\Model\Field as ForestField;
use ForestAdmin\Liana\Model\Resource as ForestResource;

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
     * @return null|object
     */
    public function getResource($recordId)
    {
        $returnedResource = null;

        if (!$this->hasIdentifier()) {
            return null;
        }

        $resourceQueryBuilder = $this->getRepository()
            ->createQueryBuilder('resource')
            ->where('resource.' . $this->getThisCollection()->getIdentifier() . ' = :identifier')
            ->setParameter('identifier', $recordId);

        $resource = $resourceQueryBuilder
            ->select('resource')
            ->getQuery()
            ->getResult(\Doctrine\ORM\Query::HYDRATE_ARRAY);

        if ($resource) {
            $returnedResource = new ForestResource(
                $this->getThisCollection(),
                $this->formatResource(reset($resource))
            );

            $relationships = $this->getThisCollection()->getRelationships();

            if (count($relationships)) {
                foreach ($relationships as $k => $field) {
                    list($tableReference, $identifier) = explode('.', $field->reference);
                    $foreignCollection = $this->findCollection($tableReference);
                    $queryBuilder = clone $resourceQueryBuilder;
                    $queryBuilder
                        ->select('relation')
                        ->join($foreignCollection->entityClassName, 'relation');
                    
                    $foreignResource = $queryBuilder
                        ->getQuery()
                        ->getResult(\Doctrine\ORM\Query::HYDRATE_ARRAY);
                    
                    if ($foreignResource) {
                        if (count($foreignResource) == 1) {
                            $resourceToInclude = new ForestResource(
                                $foreignCollection,
                                $this->formatResource(reset($foreignResource), $foreignCollection)
                            );
                            $resourceToInclude->setType($field->field);
                            $returnedResource->includeResource($resourceToInclude);
                        } else {
                            /** TODO remove trace after fix */
                            //$returnedResource['included'][$field->field] = $field->reference . ' had ' . (count($foreignResource)) . ' elements';
                        }
                    }
                }
            }
        }

        return $returnedResource;
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
     * @return array The hasMany resources with one relationships and a link to their many relationships
     */
    public function getResourceAndRelationships($recordId, $associationName)
    {

    }

    /**
     * @param array $postData
     * @return array The created resource
     */
    public function createResource($postData)
    {

        $this->getEntityManager()->flush();
    }

    /**
     * @param mixed $recordId
     * @param array $postData
     * @return array The updated resource
     */
    public function updateResource($recordId, $postData)
    {

        $this->getEntityManager()->flush();
    }

    /**
     * @param object $resource
     * @param ForestCollection|null
     * @return array
     */
    protected function formatResource($resource, $collection = null)
    {
        if(is_null($collection)) {
            $collection = $this->getThisCollection();
        }

        $ret = array();
        foreach ($collection->fields as $field) {
            /** @var ForestField $field */
            $key = $field->field;

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
        $f = $field->field;

        $value = $resource[$f];

        if (is_a($value, '\DateTime') && $field->type == 'Date') {
            /**
             * @var \DateTime $value
             */
            return $value->format('c'); // ISO-8601, takes timezone into account
        }

        if (is_array($value)) {
            $value = json_encode($value);
        }

        if ($field->type == 'Boolean') {
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
        return count($this->getThisCollection()->identifier) ? true : false;
    }

    /**
     * @param string $tableReference
     * @return null|ForestCollection
     */
    protected function findCollection($tableReference)
    {
        foreach ($this->getCollections() as $collection) {
            if ($collection->name == $tableReference) {
                return $collection;
            }
        }

        return null;
    }
}