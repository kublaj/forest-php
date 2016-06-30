<?php

namespace ForestAdmin\Liana\Schema;


use ForestAdmin\Liana\Model\Collection as ForestCollection;
use Neomerx\JsonApi\Schema\SchemaProvider;

class CollectionSchema extends SchemaProvider
{
    protected $resourceType = 'collections';

    /**
     * @param ForestCollection $collection
     * @return mixed
     */
    public function getId($collection)
    {
        return $collection->getName();
    }

    /**
     * @param ForestCollection $collection
     * @return array
     */
    public function getAttributes($collection)
    {
        $ret = array();
        
        $ret['name'] = $collection->getName();
        $ret['fields'] = $collection->getFields();
        
        if($collection->getActions()) {
            $ret['actions'] = $collection->getActions();
        }
        
        $ret['only-for-relationships'] = null;
        $ret['is-virtual'] = null;
        $ret['is-read-only'] = false;
        $ret['is-searchable'] = true;
    
        return $ret;
    }
}