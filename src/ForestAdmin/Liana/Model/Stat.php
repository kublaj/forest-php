<?php

namespace ForestAdmin\Liana\Model;

use Illuminate\Support\Fluent;

use alsvanzelf\jsonapi as JsonApi;

class Stat
{
    public $value;

    public function __construct($value)
    {
        $this->setValue($value);
        $this->setId('ew9f90ew7fwe790');
        $this->setType('stats');
    }

    public function setValue($value)
    {
        $this->value = $value;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function setType($type)
    {
        $this->type = $type;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setId($id)
    {
        $this->id = $id;
    }
    public function getId()
    {
        return $this->id;
    }

    public function formatJsonApi()
    {
        $linkPrefix = '/forest';
        $toReturn = $this->prepareJsonApiResource($linkPrefix);

        return  json_decode($toReturn->get_json());
    }

    protected function prepareJsonApiResource($linkPrefix = '/forest')
    {
        $toReturn = new JsonApi\resource($this->getType(), $this->getId());
        $toReturn->fill_data(array(
          'value' => $this->getValue()
        ));

        return $toReturn;
    }
}
