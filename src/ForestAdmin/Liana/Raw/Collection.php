<?php
/**
 * Created by PhpStorm.
 * User: jean-marc
 * Date: 20/06/16
 * Time: 16:53
 */

namespace ForestAdmin\Liana\Raw;


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
     * class used to make an entity object to interact with the collection
     * @var string
     */
    protected $entity;

    /**
     * Collection constructor.
     * @param string $name
     * @param string $entity
     * @param array $fields
     * @param array|null $actions
     */
    public function __construct($name, $entity, $fields, $actions = null)
    {
        $this->name = $name;
        $this->entity = $entity;
        $this->fields = $fields;
        $this->actions = is_null($actions) ? array() : $actions;
    }
}