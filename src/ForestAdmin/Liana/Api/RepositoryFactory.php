<?php

namespace ForestAdmin\Liana\Api;


use Doctrine\ORM\EntityRepository;

class RepositoryFactory
{
    /**
     * QueryFactory constructor.
     * @param mixed $entity
     * @return QueryInterface
     */
    static public function get($entity)
    {
        if(is_a($entity, EntityRepository::class)) {
            return new DoctrineProxy(new $entity);
        }
        
        return null;
    }
}