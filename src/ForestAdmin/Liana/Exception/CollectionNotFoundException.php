<?php

namespace ForestAdmin\Liana\Exception;


class CollectionNotFoundException extends NotFoundException
{
    public function __construct($name)
    {
        $this->message = "Collection not found: {$name}.";
    }
}