<?php

namespace ForestAdmin\Liana\Apimap;


class Attribute
{
    protected $name;
    
    protected $only_for_relationships = false;
    
    protected $virtual = false;
    
    protected $read_only = false;
    
    protected $searchable = false;

    public function __construct($name = '')
    {
        $this->setName($name);
    }

    /**
     * @return mixed
     */
    public function toArray()
    {
        return array(
            'name' => $this->getName(),
            'fields' => $this->getFields(),
            'only_for_relationships' => $this->isOnlyForRelationships(),
            'is_virtual' => $this->isVirtual(),
            'is_read_only' => $this->isReadOnly(),
            'is_searchable' => $this->isSearchable(),
        );
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getFields()
    {
        return array();
    }

    public function setOnlyForRelationships($only = true)
    {
        $this->only_for_relationships = $only ? true : false;
    }

    public function isOnlyForRelationships()
    {
        return $this->only_for_relationships;
    }

    public function setVirtual($virtual = true)
    {
        $this->virtual = $virtual ? true : false;
    }

    public function isVirtual()
    {
        return $this->virtual;
    }

    public function setReadOnly($read_only = true)
    {
        $this->read_only = $read_only ? true : false;
    }

    public function isReadOnly()
    {
        return $this->read_only;
    }

    public function setSearchable($searchable = true)
    {
        $this->searchable = $searchable ? true : false;
    }

    public function isSearchable()
    {
        return $this->searchable;
    }
}