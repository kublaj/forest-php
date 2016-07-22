<?php

namespace ForestAdmin\Liana\Adapter;


use ForestAdmin\Liana\Api\ResourceFilter;

interface QueryAdapter
{
    /**
     * Find a resource by its identifier
     *
     * @param string $recordId
     * @return array
     */
    public function getResource($recordId);

    /**
     * Find all resources by filter
     * @param ResourceFilter $filter
     * @return array
     */
    public function listResources($filter);

    /**
     * @param string $recordId
     * @param string $associationName
     * @param ResourceFilter $filter
     * @return array The hasMany resources with one relationships and a link to their many relationships
     */
    public function getHasMany($recordId, $associationName, $filter);

    /**
     * @param array $postData
     * @return array The created resource
     */
    public function createResource($postData);

    /**
     * @param string $recordId
     * @param array $postData
     * @return array The updated resource
     */
    public function updateResource($recordId, $postData);
}