<?php

namespace ForestAdmin\Liana\Apimap;


class Field
{
    /**
     * @var string
     */
    protected $field;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var string
     */
    protected $reference;

    public function toArray()
    {
        return array(
            'field' => $this->getField(),
            'type' => $this->getType(),
            'reference' => $this->getReference(),
        );
    }

    public function setField($field)
    {
        $this->field = $field;
    }

    public function getField()
    {
        return $this->field;
    }

    public function setType($type)
    {
        $this->type = $type;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setReference($reference)
    {
        $this->reference = $reference;
    }

    public function getReference()
    {
        return $this->reference;
    }
}