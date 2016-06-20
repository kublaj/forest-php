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
     * Collection constructor.
     * @param string $name
     * @param array $fields
     * @param null $actions
     */
    public function __construct($name, $fields, $actions = null)
    {
        $this->name = $name;
        $this->fields = $fields;
        $this->actions = is_null($actions) ? array() : $actions;
    }
}