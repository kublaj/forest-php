<?php
/**
 * Created by PhpStorm.
 * User: jean-marc
 * Date: 20/06/16
 * Time: 17:30
 */

namespace ForestAdmin\Liana\Raw;


use Doctrine\DBAL\Types\Type as Type; // TODO : refactor when adding another ORM

class Field
{
    public $field;
    public $type;
    public $reference;
    public $inverseOf;

    /**
     * Field constructor.
     * @param string $field
     * @param string $type (doctrine types are converted)
     * @param string|null $reference
     * @param string|null $inverseOf
     */
    public function __construct($field, $type, $reference = null, $inverseOf = null)
    {
        $this->field = $field;
        $this->setType($type);
        $this->reference = $reference;
        $this->inverseOf = $inverseOf;
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
}