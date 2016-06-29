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
    public $name;

    /**
     * @var array
     */
    public $fields;

    /**
     * @var array|null
     */
    public $actions;

    /**
     * class name for the object that will be able to interact with the collection in database
     * @var string
     */
    public $entityClassName;

    /**
     * may be more than one
     * @var array|null
     */
    public $identifier;

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
        $this->name = $name;
        $this->entityClassName = $entityClassName;
        $this->identifier = $identifier;
        $this->fields = $fields;
        $this->actions = is_null($actions) ? array() : $actions;
    }

    /**
     * @return Field[]
     */
    public function getRelationships()
    {
        return array_filter($this->fields, function($field) { return $field->reference ? true : false; });
    }

    public function getIdentifier()
    {
        return reset($this->identifier);
    }
}