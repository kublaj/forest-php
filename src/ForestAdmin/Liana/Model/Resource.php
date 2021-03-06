<?php

namespace ForestAdmin\Liana\Model;

use alsvanzelf\jsonapi as JsonApi;

class Resource
{
    /**
     * @var Collection
     */
    public $collection;

    /**
     * @var mixed
     */
    public $id;

    /**
     * @var array
     */
    public $attributes;

    /**
     * @var Resource[]
     */
    public $included;

    /**
     * @var Relationship[]
     */
    public $relationships;

    /**
     * @var string
     */
    protected $type;

    /**
     * Resource constructor.
     * @param Collection $collection
     * @param array $attributes
     * @param Relationship[] $relationships
     */
    public function __construct($collection, $attributes, $relationships = array())
    {
        $this->setCollection($collection);
        $this->setAttributes($attributes);
        $this->setRelationships($relationships);

        $id = null;
        if (array_key_exists($collection->getIdentifier(), $attributes)) {
            $id = $attributes[$collection->getIdentifier()];
        }
        $this->setId($id);

        $this->setIncluded();
    }

    /**
     * @param Collection $collection
     */
    public function setCollection($collection)
    {
        $this->collection = $collection;
        $this->setType($collection->getName());
    }

    /**
     * @return Collection
     */
    public function getCollection()
    {
        return $this->collection;
    }

    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param Resource[] $included
     */
    public function setIncluded($included = array())
    {
        $this->included = $included;
    }

    /**
     * @return Resource[]
     */
    public function getIncluded()
    {
        return $this->included;
    }

    /**
     * @param Resource $resource
     */
    public function includeResource($resource)
    {
        $this->included[] = $resource;
    }

    /**
     * Set attributes, except for keys named "id" and "type"
     * @link http://jsonapi.org/format/#document-resource-object-fields
     * @param array $attributes
     */
    public function setAttributes($attributes)
    {
        if (array_key_exists('id', $attributes)) {
            unset($attributes['id']);
        }
        /** TODO the spec also prohibits "type" as attribute key */
        $this->attributes = $attributes;
    }

    /**
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * @param Relationship[] $relationships
     */
    public function setRelationships($relationships = array())
    {
        $this->relationships = $relationships;
    }

    /**
     * @return Relationship[]
     */
    public function getRelationships()
    {
        return $this->relationships;
    }

    /**
     * @param Relationship $relationship
     */
    public function addRelationship($relationship)
    {
        $this->relationships[] = $relationship;
    }

    /**
     * @return object
     * @throws \Exception
     */
    public function formatJsonApi()
    {
        $linkPrefix = '/forest';
        $toReturn = $this->prepareJsonApiResource($linkPrefix);

        $jsonResponse = json_decode($toReturn->get_json());

        // Ugly workaround for relationships : they should only include a related link, the lib needs a resource
        $relationships = array();
        foreach ($this->getRelationships() as $relationship) {
            $ret = array();
            if($relationship->getId()) {
                // One
                $ret['links'] = array(
                    'related' => array()
                );
                $ret['data'] = array(
                    'type' => $relationship->getType(),
                    'id' => $relationship->getId(),
                );
            } else {
                // Many
                $ret['links'] = array(
                    'related' => array(
                        'href' => $linkPrefix . '/' .
                            $this->getCollection()->getName() . '/' .
                            $this->getId() . '/' .
                            $relationship->getType()
                    )
                );
            }
            $relationships[$relationship->getFieldName()] = $ret;
        }
        if ($relationships) {
            $jsonResponse->data->relationships = (object)$relationships;
        }

        // Ugly workaround: create and update actions return the wrong self link
        $jsonResponse->data->links->self = $linkPrefix . '/' . $this->getCollection()->getName() . '/' . $this->getId();

        // Ugly workaround: there is an unexpected "links" entry in the root
        unset($jsonResponse->links);

        return $jsonResponse;
    }

    /**
     * @param Resource[] $resources
     * @param int|null $totalNumberOfRows Total number of rows for the model of the resource (null: count $resources)
     * @return object
     */
    static public function formatResourcesJsonApi($resources, $totalNumberOfRows = null)
    {
        $jsonapiCollection = array();

        if($resources) {
            foreach ($resources as $resource) {
                $jsonapiCollection[] = $resource->formatJsonApi();
            }
            if(is_null($totalNumberOfRows)) {
                $totalNumberOfRows = count($resources);
            }
        }

        // Due to the current limitations of alsvanzelf/jsonapi
        // and the lack of time to fix it deeper (or to replace the jsonapi library),
        // I'm writing the collection procedure here.
        $included = array();
        foreach($jsonapiCollection as $keyResource => $resource) {
            if(!empty($resource->included)) {
                foreach($resource->included as $inclusion) {
                    $key = $inclusion->type.':'.$inclusion->id;
                    if(!array_key_exists($key, $included)) {
                        $included[$key] = $inclusion;
                    }
                }
            }
            unset($resource->included);
            $jsonapiCollection[$keyResource] = $resource;
        }
        $returnedCollection = array();
        $returnedCollection['data'] = array();
        foreach($jsonapiCollection as $resource) {
            $returnedCollection['data'][] = $resource->data;
        }
        $returnedCollection['included'] = array_values($included);
        $returnedCollection['meta'] = array('count' => $totalNumberOfRows);
        $jsonResponse = json_decode(json_encode($returnedCollection));

        return $jsonResponse;
    }

    /**
     * @param $linkPrefix
     * @return JsonApi\resource
     * @throws \Exception
     */
    protected function prepareJsonApiResource($linkPrefix = '/forest')
    {
        $toReturn = new JsonApi\resource($this->getCollection()->getName(), $this->getId());
        $toReturn->fill_data($this->getAttributes());

        foreach($this->getRelationships() as $relationship) {
            $resource = new JsonApi\resource($relationship->getType(), $relationship->getId());
            $toReturn->add_relation($relationship->getType(), $resource);
        }

        foreach ($this->getIncluded() as $resource) {
            $toInclude = new JsonApi\resource($resource->getCollection()->getName(), $resource->getId());
            // NOTE : alsvanzelf/jsonapi takes current request to build set_self_link
            // => included resources must set it "manually"
            $toInclude->set_self_link($linkPrefix . '/' . $resource->getCollection()->getName() . '/' . $resource->getId());
            $toInclude->fill_data($resource->getAttributes());
            $toReturn->add_included_resource($toInclude);
        }

        return $toReturn;
    }
}