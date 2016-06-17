<?php

use ForestAdmin\Liana\Analyzer\DoctrineAnalyzer as DoctrineAnalyzer;

class DoctrineAnalyzerTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var DoctrineAnalyzer
     */
    protected $da;

    protected $map;

    public function __construct()
    {
        $data = file_get_contents(__DIR__ . '/example-metadata');

        $this->da = new DoctrineAnalyzer;
        $this->da
            ->setMetadata(unserialize($data));
    }

    public function setUp()
    {
        $metadata = $this->da->getMetadata();
        $this->assertNotEmpty($metadata);
        $this->assertInstanceOf(\Doctrine\Common\Persistence\Mapping\ClassMetadata::class, (reset($metadata)));
        $this->map = $this->da->analyze();
        $this->assertTrue(is_array($this->map));
    }

    public function testDataStructure()
    {
        $map = $this->map;

        $this->assertArrayHasKey('data', $map);

        $this->assertCount(110, $map['data']);
        $firstData = reset($map['data']);
        $this->assertArrayHasKey('name', $firstData);
        $this->assertArrayHasKey('fields', $firstData);
        $this->assertArrayHasKey('actions', $firstData);
    }

    public function testFieldStructure()
    {
        $map = $this->map;
        $firstData = reset($map['data']);

        $this->assertArrayHasKey('field', $firstData['fields'][0]);
        $this->assertArrayHasKey('type', $firstData['fields'][0]);
        $this->assertArrayHasKey('reference', $firstData['fields'][0]);
    }

    public function testFieldTypes()
    {
        $tables = $this->map['data'];

        // table:address: street:VARCHAR, number:INT, supplements_address:LONGTEXT, longitude:DOUBLE
        $table_address = $tables['AppBundle\Entity\Address'];
        $fields_address = $table_address['fields'];
        $this->assertEquals('address', $table_address['name']);
        $this->assertCount(8, $fields_address);
        $this->assertEquals('street', $fields_address[1]['field']);
        $this->assertEquals('String', $fields_address[1]['type']);
        $this->assertEquals('number', $fields_address[2]['field']);
        $this->assertEquals('Number', $fields_address[2]['type']);
        $this->assertEquals('supplements_address', $fields_address[5]['field']);
        $this->assertEquals('String', $fields_address[5]['type']);
        $this->assertEquals('longitude', $fields_address[6]['field']);
        $this->assertEquals('Number', $fields_address[6]['type']);

        // table:asset: you_are:SMALLINT, created_at:DATETIME
        $table_asset = $tables['AppBundle\Entity\Asset'];
        $fields_asset = $table_asset['fields'];
        $this->assertEquals('asset', $table_asset['name']);
        $this->assertCount(15, $fields_asset);
        $this->assertEquals('you_are', $fields_asset[7]['field']);
        $this->assertEquals('Number', $fields_asset[7]['type']);
        $this->assertEquals('created_at', $fields_asset[11]['field']);
        $this->assertEquals('Date', $fields_asset[11]['type']);

        // table:media__media: enabled:TINYINT(1)=BOOLEAN, length:DECIMAL
        $table_media__media = $tables['Application\Sonata\MediaBundle\Entity\Media'];
        $fields_media__media = $table_media__media['fields'];
        $this->assertEquals('media__media', $table_media__media['name']);
        $this->assertCount(24, $fields_media__media);
        $this->assertEquals('enabled', $fields_media__media[2]['field']);
        $this->assertEquals('Boolean', $fields_media__media[2]['type']);
        $this->assertEquals('length', $fields_media__media[9]['field']);
        $this->assertEquals('Number', $fields_media__media[9]['type']);

        // no new data type
    }

    public function testFieldAssociations()
    {
        $tables = $this->map['data'];
        $table_billing = $tables['AppBundle\Entity\Billing'];
        $fields_billing = $table_billing['fields'];
        $this->assertNull($fields_billing[0]['reference']);
        $this->assertEquals('user_id', $fields_billing[6]['field']);
        $this->assertEquals('users.id', $fields_billing[6]['reference']);
    }

    public function testMeta()
    {
        $map = $this->map;

        $this->assertArrayHasKey('meta', $map);
        $this->assertArrayHasKey('liana', $map['meta']);
        $this->assertArrayHasKey('liana-version', $map['meta']);
    }

    public function tearDown()
    {

    }
}
