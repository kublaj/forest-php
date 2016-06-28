<?php

namespace ForestAdmin\Liana\Adapter;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

class DoctrineAdapter implements QueryAdapter
{
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
     * @param EntityManager $entityManager
     * @param EntityRepository $repository
     */
    public function __construct($entityManager, $repository)
    {
        $this->setEntityManager($entityManager);
        $this->setRepository($repository);
    }

    /**
     * @param EntityManager $entityManager
     */
    public function setEntityManager($entityManager)
    {
        $this->entityManager = $entityManager;
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
     */
    public function setRepository($repository)
    {
        $this->repository = $repository;
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
     * @return array
     */
    public function getResource($recordId)
    {
        return $this->getRepository()->find($recordId);
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
}