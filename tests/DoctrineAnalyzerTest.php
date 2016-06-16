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
        $this->da = new DoctrineAnalyzer;
        $this->da
            ->setDatabaseUrl('mysql://root:root@projects.local:3306/drapo');

        $this->map = $this->da->analyze();
        $this->assertTrue(is_array($this->map));

    }

    public function setUp()
    {
    }

    public function testData()
    {
        $map = $this->map;

        $this->assertArrayHasKey('data', $map);

        $this->assertCount(90, $map['data']);
        $this->assertArrayHasKey('name', $map['data'][0]);
        $this->assertArrayHasKey('fields', $map['data'][0]);
        $this->assertArrayHasKey('actions', $map['data'][0]);
    }

    public function testFields()
    {
        $map = $this->map;

        $this->assertArrayHasKey('field', $map['data'][0]['fields'][0]);
        $this->assertArrayHasKey('type', $map['data'][0]['fields'][0]);
        $this->assertArrayHasKey('reference', $map['data'][0]['fields'][0]);
    }

    public function testFieldTypes()
    {
        $tables = $this->map['data'];

        // table: acl_classes: id:INT, class_type:VARCHAR
        $table_acl_classes = $tables[0];
        $fields_acl_classes = $table_acl_classes['fields'];
        $this->assertEquals('acl_classes', $table_acl_classes['name']);
        $this->assertCount(2, $fields_acl_classes);
        $this->assertEquals('id', $fields_acl_classes[0]['field']);
        $this->assertEquals('Number', $fields_acl_classes[0]['type']);
        $this->assertEquals('class_type', $fields_acl_classes[1]['field']);
        $this->assertEquals('String', $fields_acl_classes[1]['type']);

        // table:acl_entries: ace_order:SMALLINT, granting:TINYINT(1)=BOOLEAN
        $table_acl_entries = $tables[1];
        $fields_acl_entries = $table_acl_entries['fields'];
        $this->assertEquals('acl_entries', $table_acl_entries['name']);
        $this->assertCount(11, $fields_acl_entries);
        $this->assertEquals('ace_order', $fields_acl_entries[5]['field']);
        $this->assertEquals('granting', $fields_acl_entries[7]['field']);
        $this->assertEquals('Boolean', $fields_acl_entries[7]['type']);
        $this->assertEquals('Number', $fields_acl_entries[5]['type']);

        // table:address: supplements_address:LONGTEXT, longitude:DOUBLE
        $table_address = $tables[5];
        $fields_address = $table_address['fields'];
        $this->assertEquals('address', $table_address['name']);
        $this->assertCount(8, $fields_address);
        $this->assertEquals('supplements_address', $fields_address[5]['field']);
        $this->assertEquals('String', $fields_address[5]['type']);
        $this->assertEquals('longitude', $fields_address[6]['field']);
        $this->assertEquals('Number', $fields_address[6]['type']);

        // table:asset: created_at:DATETIME
        $table_asset = $tables[7];
        $fields_asset = $table_asset['fields'];
        $this->assertEquals('asset', $table_asset['name']);
        $this->assertCount(14, $fields_asset);
        $this->assertEquals('created_at', $fields_asset[13]['field']);
        $this->assertEquals('Date', $fields_asset[13]['type']);

        // table:media__media: length:DECIMAL
        $table_media__media = $tables[43];
        $fields_media__media = $table_media__media['fields'];
        $this->assertEquals('media__media', $table_media__media['name']);
        $this->assertCount(23, $fields_media__media);
        $this->assertEquals('length', $fields_media__media[11]['field']);
        $this->assertEquals('Number', $fields_media__media[11]['type']);

        // no new data type
    }

    public function testFieldAssociations()
    {
        $tables = $this->map['data'];
        $table_acl_entries = $tables[1];
        $fields_acl_entries = $table_acl_entries['fields'];
        $this->assertNull($fields_acl_entries[0]['reference']);
        $this->assertEquals('object_identity_id', $fields_acl_entries[1]['field']);
        $this->assertEquals('acl_object_identities.id', $fields_acl_entries[1]['reference']);
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
