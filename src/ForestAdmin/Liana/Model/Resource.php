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
     * @var array
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
     * @param string[] $relationships
     */
    public function __construct($collection, $attributes, $relationships = array())
    {
        $this->setCollection($collection);
        $this->setAttributes($attributes);
        $this->setRelationships($relationships);

        $id = null;
        if(array_key_exists($collection->getIdentifier(), $attributes)) {
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
        if(array_key_exists('id', $attributes)) {
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
     * @param string[] $relationships
     */
    public function setRelationships($relationships = array())
    {
        $this->relationships = $relationships;
    }

    /**
     * @return string[]
     */
    public function getRelationships()
    {
        return $this->relationships;
    }

    /**
     * @param string $name
     */
    public function addRelationship($name)
    {
        $this->relationships[] = $name;
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
        foreach($this->getRelationships() as $relationship) {
            $relationships[$relationship] = array(
                'links' => array(
                    'related' => $linkPrefix . '/' . $this->getCollection()->getName() . '/' . $this->getId() . '/' . $relationship
                )
            );
        }
        if($relationships) {
            $jsonResponse->data->relationships = (object)$relationships;
        }

        // Ugly workaround: create and update actions return the wrong related link
        $jsonResponse->data->links->self = $linkPrefix . '/' . $this->getCollection()->getName() . '/' . $this->getId();

        // Ugly workaround: there is an unexpected "links" entry in the root
        unset($jsonResponse->links);

        return $jsonResponse;
    }

    /**
     * @param Resource[] $resources
     * @return object
     */
    static public function formatResourcesJsonApi($resources)
    {
        if(!$resources) {
            return (object)array();
        }

        $linkPrefix = '/forest';
        $firstResource = reset($resources);
        $jsonapiCollection = array();
        foreach($resources as $resource) {
            $jsonapiCollection[] = $resource->prepareJsonApiResource($linkPrefix);
        }
        $toReturn = new JsonApi\collection($firstResource->getType());
        $toReturn->fill_collection($jsonapiCollection);
        
        return json_decode($toReturn->get_json());
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