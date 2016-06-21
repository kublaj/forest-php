<?php

use ForestAdmin\Liana\Analyzer\DoctrineAnalyzer;
use ForestAdmin\Liana\Raw\Collection as ForestCollection;
use ForestAdmin\Liana\Api\Api as ForestApi;

class ApiTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var DoctrineAnalyzer
     */
    protected $da;

    /**
     * @var ForestCollection[]
     */
    protected $map;

    public function setUp()
    {
        $data = file_get_contents(__DIR__ . '/example-metadata');

        $this->da = new DoctrineAnalyzer;
        $this->da
            ->setMetadata(unserialize($data));

        $this->map = $this->da->analyze();
    }

    public function testApimap()
    {
        $apimap = ForestApi::getApimap($this->map);
        $decodedApimap = json_decode($apimap);
        $this->assertTrue(is_object($decodedApimap));
        $this->assertObjectHasAttribute('data', $decodedApimap);
        $this->assertTrue(is_array($decodedApimap->data));
        $this->assertCount(116, $decodedApimap->data);

        $data = $decodedApimap->data[59];
        $this->assertObjectHasAttribute('type', $data);
        $this->assertEquals('collections', $data->type);
        $this->assertObjectHasAttribute('id', $data);
        $this->assertEquals('asset', $data->id);
        $this->assertObjectHasAttribute('attributes', $data);
        $this->assertObjectHasAttribute('name', $data->attributes);
        $this->assertEquals('asset', $data->attributes->name);
        $this->assertObjectHasAttribute('fields', $data->attributes);
        $this->assertCount(15, $data->attributes->fields);
    }

    public function tearDown()
    {

    }
}
