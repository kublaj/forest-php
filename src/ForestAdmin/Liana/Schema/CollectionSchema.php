<?php
/**
 * Created by PhpStorm.
 * User: jean-marc
 * Date: 20/06/16
 * Time: 16:40
 */

namespace ForestAdmin\Liana\Schema;


use ForestAdmin\Liana\Raw\Collection;
use Neomerx\JsonApi\Schema\SchemaProvider;

class CollectionSchema extends SchemaProvider
{
    protected $resourceType = 'collections';

    /**
     * @param Collection $collection
     * @return mixed
     */
    public function getId($collection)
    {
        return $collection->name;
    }

    public function getAttributes($collection)
    {
        
    }
}