<?php

namespace ForestAdmin\Liana\Model;

use Doctrine\DBAL\Types\Type as Type; // TODO : refactor when adding another ORM

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
     * @var null|string
     */
    protected $reference;

    /**
     * @var null|string
     */
    protected $inverseOf;

    /**
     * Field constructor.
     * @param string $fieldName
     * @param string $type (doctrine types are converted)
     * @param string|null $reference
     * @param string|null $inverseOf
     */
    public function __construct($fieldName, $type, $reference = null, $inverseOf = null)
    {
        $this->setField($fieldName);
        $this->setType($type);
        $this->setReference($reference);
        $this->setInverseOf($inverseOf);
    }

    /**
     * @param string $field
     */
    public function setField($field)
    {
        $this->field = $field;
    }

    /**
     * @return string
     */
    public function getField()
    {
        return $this->field;
    }

    public function setType($type)
    {
        switch ($type) {
            case Type::INTEGER:
            case Type::SMALLINT:
            case Type::FLOAT:
            case Type::DECIMAL:
                $this->type = 'Number';
                break;
            case Type::STRING:
            case Type::TEXT:
                $this->type = 'String';
                break;
            case Type::BOOLEAN:
                $this->type = 'Boolean';
                break;
            case Type::DATE:
            case Type::DATETIME:
            case Type::DATETIMETZ:
                $this->type = 'Date';
                break;
            default:
                $this->type = $type;
        }
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return bool
     */
    public function isTypeToMany()
    {
        return is_array($this->type) && $this->type[0] == 'Number';
    }

    /**
     * @param null|string $inverseOf
     */
    public function setInverseOf($inverseOf)
    {
        $this->inverseOf = $inverseOf;
    }

    /**
     * @return null|string
     */
    public function getInverseOf()
    {
        return $this->inverseOf;
    }

    /**
     * @param null|string $reference
     */
    public function setReference($reference)
    {
        $this->reference = $reference;
    }

    /**
     * @return null|string
     */
    public function getReference()
    {
        return $this->reference;
    }

    /**
     * @return string|null
     */
    public function getReferencedTable()
    {
        $ref = $this->getReferenceElements();

        if(is_array($ref) && count($ref) == 2) {
            return $ref[0];
        }

        return null;
    }
    /**
     * @return string|null
     */
    public function getReferencedField()
    {
        $ref = $this->getReferenceElements();

        if(is_array($ref) && count($ref) == 2) {
            return $ref[1];
        }

        return null;
    }

    public function toArray()
    {
        $ret = array(
            'field' => $this->getField(),
            'type' => $this->getType()
        );
        if($this->getReference()) {
            $ret['reference'] = $this->getReference();
        }
        if($this->getInverseOf()) {
            $ret['inverseOf'] = $this->getInverseOf();
        }
        
        return $ret;
    }

    /**
     * @return array
     */
    protected function getReferenceElements()
    {
        return $this->getReference() ? explode('.', $this->getReference()) : null;
    }
}