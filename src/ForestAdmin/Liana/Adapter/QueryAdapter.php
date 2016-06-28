<?php

namespace ForestAdmin\Liana\Adapter;


interface QueryAdapter
{
    /**
     * Find a resource by its identifier
     *
     * @param mixed $recordId
     * @return array
     */
    public function getResource($recordId);

    /**
     * Find all resources by filter
     * @param ResourceFilter $filter
     * @return array
     */
    public function getResources($filter);

    /**
     * @param string $modelName
     * @param mixed $recordId
     * @param string $associationName
     * @return array The hasMany resources with one relationships and a link to their many relationships
     */
    public function getResourceAndRelationships($recordId, $associationName);

    /**
     * @param string $modelName
     * @param array $postData
     * @return array The created resource
     */
    public function createResource($postData);

    /**
     * @param string $modelName
     * @param mixed $recordId
     * @param array $postData
     * @return array The updated resource
     */
    public function updateResource($recordId, $postData);
}