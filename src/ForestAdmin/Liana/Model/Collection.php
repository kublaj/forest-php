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
     * Collection constructor.
     * @param string $name
     * @param string $entityClassName
     * @param array $fields
     * @param array|null $actions
     */
    public function __construct($name, $entityClassName, $fields, $actions = null)
    {
        $this->name = $name;
        $this->entityClassName = $entityClassName;
        $this->fields = $fields;
        $this->actions = is_null($actions) ? array() : $actions;
    }
}