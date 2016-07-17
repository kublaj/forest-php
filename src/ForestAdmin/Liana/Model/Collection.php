<?php
/**
 * Created by PhpStorm.
 * User: jean-marc
 * Date: 20/06/16
 * Time: 16:53
 */

namespace ForestAdmin\Liana\Model;


use ForestAdmin\Liana\Exception\RelationshipNotFoundException;

class Collection
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var Field[]
     */
    protected $fields;

    /**
     * @var array|null
     */
    protected $actions;

    /**
     * class name for the object that will be able to interact with the collection in database
     * @var string
     */
    protected $entityClassName;

    /**
     * may be more than one
     * @var array|null
     */
    protected $identifier;

    /**
     * @var Field[]
     */
    protected $relationships;

    /**
     * Collection constructor.
     * @param string $name
     * @param string $entityClassName
     * @param array $identifier
     * @param Field[] $fields
     * @param array|null $actions
     */
    public function __construct($name, $entityClassName, $identifier, $fields, $actions = null)
    {
        $this->setName($name);
        $this->setEntityClassName($entityClassName);
        $this->setIdentifier($identifier);
        $this->setFields($fields);
        $this->setActions($actions);
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param array|null $actions
     */
    public function setActions($actions = null)
    {
        $this->actions = is_null($actions) ? array() : $actions;
    }

    /**
     * @return array|null
     */
    public function getActions()
    {
        return $this->actions;
    }

    /**
     * @param string $entityClassName
     */
    public function setEntityClassName($entityClassName)
    {
        $this->entityClassName = $entityClassName;
    }

    /**
     * @return string
     */
    public function getEntityClassName()
    {
        return $this->entityClassName;
    }

    /**
     * @param Field[] $fields
     */
    public function setFields($fields)
    {
        $this->fields = array();
        
        foreach($fields as $field) {
            $this->fields[$field->getField()] = $field;
        }

        $this->relationships = array();
        $filtered = array_filter($this->fields, function($field) {
            /** @var Field $field */
            return $field->getReference() ? true : false; 
        });

        foreach($filtered as $field) {
            /** @var Field $field */
            list($table, $id) = explode('.', $field->getReference());
            $this->relationships[$table] = $field;
        }
    }

    /**
     * @return Field[]
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * @param array|null $identifier
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;
    }

    public function getIdentifier()
    {
        return reset($this->identifier);
    }

    /**
     * @return Field[]
     */
    public function getRelationships()
    {
        return $this->relationships;
    }

    /**
     * @param $name
     * @return Field
     * @throws RelationshipNotFoundException
     */
    public function getRelationship($name)
    {
        if($this->hasRelationship($name)) {
            return $this->relationships[$name];
        }
        
        throw new RelationshipNotFoundException($name, array_keys($this->relationships));
    }

    /**
     * @param $name
     * @return Field
     * @throws FieldNotFoundException
     */
    public function getField($name)
    {
        if($this->hasField($name)) {
            return $this->fields[$name];
        }

        throw new FieldNotFoundException($name, array_keys($this->relationships));
    }

    public function convertForApimap()
    {
        $fields = array();
        foreach($this->getFields() as $field) {
            $fields[] = $field->toArray();
        }
        $this->fields = $fields;
    }

    /**
     * @param $name
     * @return bool
     */
    public function hasRelationship($name)
    {
        return array_key_exists($name, $this->relationships);
    }

    /**
     * @param $name
     * @return bool
     */
    public function hasField($name)
    {
        return array_key_exists($name, $this->fields);
    }
}