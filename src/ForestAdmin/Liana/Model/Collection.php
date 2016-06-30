<?php
/**
 * Created by PhpStorm.
 * User: jean-marc
 * Date: 20/06/16
 * Time: 16:53
 */

namespace ForestAdmin\Liana\Model;


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
        $this->fields = $fields;
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
        return array_filter($this->fields, function($field) { return $field->getReference() ? true : false; });
    }
}