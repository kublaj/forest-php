<?php

namespace ForestAdmin\Liana\Apimap;


class Map
{
    /**
     * @var array
     */
    protected $entities;

    /**
     * @var array
     */
    protected $meta;

    /**
     * @var array
     */
    protected $included;

    public function __construct()
    {
        $this->entities = array();
        $this->meta = array();
        $this->included = array();
    }

    public function toArray()
    {
        return array(
            'data' => $this->getEntities(),
            'meta' => $this->getMeta(),
            'included' => $this->getIncluded(),
        );
    }

    public function getEntities()
    {
        $ret = array();

        if (count($this->entities)) {
            foreach ($this->entities as $entity) {
                $ret[] = $entity->toArray();
            }
        }

        return $ret;
    }

    /**
     * @param Entity $entity
     */
    public function addEntity(Entity $entity)
    {
        $this->entities[] = $entity;
    }

    public function getMeta()
    {
        return $this->meta;
    }

    public function getIncluded()
    {
        return $this->included;
    }
}