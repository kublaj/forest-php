<?php

namespace ForestAdmin\Liana\Apimap;


class Entity
{
    /**
     * @return mixed
     */
    public function toArray()
    {
        return array(
            'id' => 'id',
            'type' => 'type',
            'attributes' => array(),
            'links' => array(),
            'relationships' => array(),
        );
    }
}