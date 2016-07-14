<?php

namespace ForestAdmin\Liana\Api;

use ForestAdmin\Liana\Model\Collection as ForestCollection;
use ForestAdmin\Liana\Schema\CollectionSchema;
use Neomerx\JsonApi\Encoder\Encoder;
use Neomerx\JsonApi\Encoder\EncoderOptions;


class Map
{
    /**
     * @var array
     */
    protected $encoderConfig = array(
        ForestCollection::class => CollectionSchema::class,
    );

    /**
     * @var ForestCollection[]
     */
    protected $collections;

    /**
     * @var array
     */
    protected $typeToClassName;

    /**
     * Map constructor.
     * @param ForestCollection[]|null $collections
     */
    public function __construct($collections = null)
    {
        if($collections) {
            $this->setApimap($collections);
        }
    }

    /**
     * @param ForestCollection[] $collections
     */
    public function setApimap($collections)
    {
        $this->typeToClassName = array();

        foreach($collections as $className => $collection) {
            $this->typeToClassName[$collection->getName()] = $className;
            $collection->convertForApimap();
            $collections[$className] = $collection;
        }

        $this->collections = $collections;
    }

    /**
     * @return string
     */
    public function getApimap()
    {
        return $this->encode($this->collections);
    }

    /**
     * @param array $data
     */
    protected function encode($data)
    {
        $encoder = Encoder::instance(
            $this->encoderConfig,
            new EncoderOptions(JSON_UNESCAPED_SLASHES, '')
        );

        $ret = $encoder->encodeData($data) . PHP_EOL;
        // TODO add relationships
        return $ret;
    }
}