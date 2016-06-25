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
        $this->assertCount(118, $apimap->data);

        $data = $apimap->data[59];
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
