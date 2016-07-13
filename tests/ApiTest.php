<?php

use ForestAdmin\Liana\Analyzer\DoctrineAnalyzer;
use ForestAdmin\Liana\Model\Collection as ForestCollection;
use ForestAdmin\Liana\Api\Map as Apimap;
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;

class ApiTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var DoctrineAnalyzer
     */
    protected $da;

    /**
     * @var Collection[]
     */
    protected $collections;

    /**
     * @var Apimap
     */
    protected $map;

    public function setUp()
    {
        $data = file_get_contents(__DIR__ . '/example-metadata');
        //$params = array('url' => 'mysql://root:secret@localhost:33060/drapo');
        //$connection = \Doctrine\DBAL\DriverManager::getConnection($params);
        //$config = Setup::createConfiguration();
        //    *     $paths = array('/path/to/entity/mapping/files');
        //    *     $config = Setup::createAnnotationMetadataConfiguration($paths);
        //$entityManager = EntityManager::create($connection, $config);

        $this->da = new DoctrineAnalyzer;
        //$this->da->setEntityManager($entityManager);
        $this->da
            ->setMetadata(unserialize($data));

        $this->collections = $this->da->analyze();
        $this->map = new Apimap($this->collections);
    }

    public function testApimap()
    {
        $apimap = json_decode($this->map->getApimap());
        $this->assertTrue(is_object($apimap));
        $this->assertObjectHasAttribute('data', $apimap);
        $this->assertTrue(is_array($apimap->data));

        $data = $apimap->data[59];
        $this->assertObjectHasAttribute('type', $data);
        $this->assertEquals('collections', $data->type);
        $this->assertObjectHasAttribute('id', $data);
        $this->assertEquals('units', $data->id);
        $this->assertObjectHasAttribute('attributes', $data);
        $this->assertObjectHasAttribute('name', $data->attributes);
        $this->assertEquals('units', $data->attributes->name);
        $this->assertObjectHasAttribute('fields', $data->attributes);
        $this->assertCount(6, $data->attributes->fields);

        $attributes = (array)$data->attributes;
        $this->assertArrayHasKey('only-for-relationships', $attributes);
        $this->assertNull($attributes['only-for-relationships']);
        $this->assertArrayHasKey('is-virtual', $attributes);
        $this->assertNull($attributes['is-virtual']);
        $this->assertArrayHasKey('is-read-only', $attributes);
        $this->assertFalse($attributes['is-read-only']);
        $this->assertArrayHasKey('is-searchable', $attributes);
        $this->assertTrue($attributes['is-searchable']);

        $this->assertObjectHasAttribute('links', $data);

        $this->assertObjectHasAttribute('field', $data->attributes->fields[0]);
        $this->assertEquals('id', $data->attributes->fields[0]->field);
        $this->assertEquals('Number', $data->attributes->fields[0]->type);
        $this->assertObjectNotHasAttribute('reference', $data->attributes->fields[0]);
        $this->assertObjectNotHasAttribute('inverseOf', $data->attributes->fields[0]);

        $this->assertEquals('translations', $data->attributes->fields[4]->field);
        $this->assertTrue(is_array($data->attributes->fields[4]->type));
        $this->assertEquals('Number', $data->attributes->fields[4]->type[0]);
        $this->assertObjectHasAttribute('reference', $data->attributes->fields[4]);
        $this->assertEquals('unit_translation.id', $data->attributes->fields[4]->reference);
        $this->assertObjectNotHasAttribute('inverseOf', $data->attributes->fields[4]);

        $data = $apimap->data[77];
        $this->assertEquals('user', $data->attributes->fields[13]->field);
        $this->assertEquals('Number', $data->attributes->fields[13]->type);
        $this->assertObjectHasAttribute('reference', $data->attributes->fields[13]);
        $this->assertEquals('users.id', $data->attributes->fields[13]->reference);
        $this->assertObjectHasAttribute('inverseOf', $data->attributes->fields[13]);
        $this->assertEquals('company', $data->attributes->fields[13]->inverseOf);

    }

    public function tearDown()
    {

    }
}
