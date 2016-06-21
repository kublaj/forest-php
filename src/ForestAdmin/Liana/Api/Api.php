<?php

namespace ForestAdmin\Liana\Api;

use ForestAdmin\Liana\Raw\Collection as ForestCollection;
use ForestAdmin\Liana\Schema\CollectionSchema;
use Neomerx\JsonApi\Encoder\Encoder;
use Neomerx\JsonApi\Encoder\EncoderOptions;


class Api
{
    /**
     * @param ForestCollection[] $collections
     * @return string
     */
    static public function getApimap($collections)
    {
        $encoder = Encoder::instance(array(
            ForestCollection::class => CollectionSchema::class,
        ), new EncoderOptions(JSON_PRETTY_PRINT, '/forestadmin'));

        $ret = $encoder->encodeData($collections) . PHP_EOL;
        // TODO add relationships
        return $ret;
    }
}