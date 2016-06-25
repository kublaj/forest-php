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
     * object that will be able to interact with the collection data
     * @var object
     */
    public $repository;

    /**
     * Collection constructor.
     * @param string $name
     * @param object $repository
     * @param array $fields
     * @param array|null $actions
     */
    public function __construct($name, $repository, $fields, $actions = null)
    {
        $this->name = $name;
        $this->repository = $repository;
        $this->fields = $fields;
        $this->actions = is_null($actions) ? array() : $actions;
    }
}