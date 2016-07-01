<?php

namespace ForestAdmin\Liana\Exception;


class AssociationNotFoundException extends NotFoundException
{
    public function __construct($name)
    {
        $this->message = "Association not found: {$name}.";
    }
}