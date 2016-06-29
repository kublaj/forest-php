<?php

namespace ForestAdmin\Liana\Model;


class Resource
{
    /**
     * @var Collection
     */
    public $collection;

    /**
     * @var mixed
     */
    public $id;
    
    /**
     * @var array
     */
    public $attributes;

    /**
     * @var Resource[]
     */
    public $included;

    /**
     * @var string
     */
    protected $type;

    /**
     * Resource constructor.
     * @param Collection $collection
     * @param array $attributes
     * @param mixed|null $id
     */
    public function __construct($collection, $attributes, $id = null)
    {
        $this->setCollection($collection);
        $this->setAttributes($attributes);

        if(is_null($id) && array_key_exists($collection->getIdentifier(), $attributes)) {
            $id = $attributes[$collection->getIdentifier()];
        }
        $this->setId($id);
        
        $this->setIncluded();
    }

    /**
     * @param Collection $collection
     */
    public function setCollection($collection)
    {
        $this->collection = $collection;
        $this->setType($collection->name);
    }

    /**
     * @return Collection
     */
    public function getCollection()
    {
        return $this->collection;
    }

    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param Resource[] $included
     */
    public function setIncluded($included = array())
    {
        $this->included = $included;
    }

    /**
     * @return Resource[]
     */
    public function getIncluded()
    {
        return $this->included;
    }

    /**
     * @param Resource $resource
     */
    public function includeResource($resource)
    {
        $this->included[] = $resource;
    }

    /**
     * @param array $attributes
     */
    public function setAttributes($attributes)
    {
        $this->attributes = $attributes;
    }

    /**
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }
}