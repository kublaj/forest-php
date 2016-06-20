<?php

use ForestAdmin\Liana\Analyzer\DoctrineAnalyzer as DoctrineAnalyzer;
use ForestAdmin\Liana\Raw\Collection as Collection;

class DoctrineAnalyzerTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var DoctrineAnalyzer
     */
    protected $da;

    /**
     * @var Collection[]
     */
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
        $collections = $this->map;

        $this->assertCount(116, $collections);
        $firstCollection = reset($collections);
        $this->assertInstanceOf(\ForestAdmin\Liana\Raw\Collection::class, $firstCollection);
    }

    public function testFieldStructure()
    {
        $collections = $this->map;
        $firstCollection = reset($collections);
        $firstField = reset($firstCollection->fields);
        $this->assertInstanceOf(\ForestAdmin\Liana\Raw\Field::class, $firstField);
    }

    public function testFieldTypes()
    {
        $collections = $this->map;

        // table:address: street:VARCHAR, number:INT, supplements_address:LONGTEXT, longitude:DOUBLE
        $collection_address = $collections['AppBundle\Entity\Address'];
        $fields_address = $collection_address->fields;
        $this->assertEquals('address', $collection_address->name);
        $this->assertCount(8, $fields_address);
        $this->assertEquals('street', $fields_address[1]->field);
        $this->assertEquals('String', $fields_address[1]->type);
        $this->assertEquals('number', $fields_address[2]->field);
        $this->assertEquals('Number', $fields_address[2]->type);
        $this->assertEquals('supplements_address', $fields_address[5]->field);
        $this->assertEquals('String', $fields_address[5]->type);
        $this->assertEquals('longitude', $fields_address[6]->field);
        $this->assertEquals('Number', $fields_address[6]->type);

        // table:asset: you_are:SMALLINT, created_at:DATETIME
        $collection_asset = $collections['AppBundle\Entity\Asset'];
        $fields_asset = $collection_asset->fields;
        $this->assertEquals('asset', $collection_asset->name);
        $this->assertCount(15, $fields_asset);
        $this->assertEquals('you_are', $fields_asset[7]->field);
        $this->assertEquals('Number', $fields_asset[7]->type);
        $this->assertEquals('created_at', $fields_asset[11]->field);
        $this->assertEquals('Date', $fields_asset[11]->type);

        // table:media__media: enabled:TINYINT(1)=BOOLEAN, length:DECIMAL
        $collection_media__media = $collections['Application\Sonata\MediaBundle\Entity\Media'];
        $fields_media__media = $collection_media__media->fields;
        $this->assertEquals('media__media', $collection_media__media->name);
        $this->assertCount(24, $fields_media__media);
        $this->assertEquals('enabled', $fields_media__media[2]->field);
        $this->assertEquals('Boolean', $fields_media__media[2]->type);
        $this->assertEquals('length', $fields_media__media[9]->field);
        $this->assertEquals('Number', $fields_media__media[9]->type);

        // no new data type
    }

    public function testFieldAssociations()
    {
        $collections = $this->map;

        $collection_billing = $collections['AppBundle\Entity\Billing'];
        $fields_billing = $collection_billing->fields;
        $this->assertNull($fields_billing[0]->reference);
        //Many-to-One
        $this->assertEquals('user_id', $fields_billing[6]->field);
        $this->assertEquals('Number', $fields_billing[6]->type);
        $this->assertEquals('users.id', $fields_billing[6]->reference);
        $this->assertEquals('billing', $fields_billing[6]->inverseOf);

        $collection_users = $collections['AppBundle\Entity\User'];
        $fields_users = $collection_users->fields;
        $this->assertCount(49, $fields_users);
        //One-to-One
        $this->assertEquals('picture_id', $fields_users[48]->field);
        $this->assertEquals('Number', $fields_users[48]->type);
        $this->assertEquals('media__media.id', $fields_users[48]->reference);
        //One-to-Many
        $this->assertEquals('user', $fields_users[47]->field);
        $this->assertTrue(is_array($fields_users[47]->type));
        $this->assertEquals('Number', reset($fields_users[47]->type));
        $this->assertEquals('billing.user_id', $fields_users[47]->reference);
        $this->assertEquals('billing', $fields_users[47]->inverseOf);

        //Many-to-Many, when no joinColumns nor joinTable exist in doctrine mapping
        $collection_fos_user_user_group = $collections['fos_user_user_group'];
        $fields_fos_user_user_group = $collection_fos_user_user_group->fields;
        $this->assertCount(2, $fields_fos_user_user_group);
        // +1 : left_side (to be defined with Sandro)
        $this->assertEquals('group_id', $fields_fos_user_user_group[0]->field);
        $this->assertEquals('Number', $fields_fos_user_user_group[0]->type);
        $this->assertEquals('fos_user_group.id', $fields_fos_user_user_group[0]->reference);
        $this->assertEquals('user_id', $fields_fos_user_user_group[1]->field);
        $this->assertEquals('Number', $fields_fos_user_user_group[1]->type);
        $this->assertEquals('users.id', $fields_fos_user_user_group[1]->reference);
        //+ test left_side

        //Many-to-Many, through getSchemaForOneToManyAssociation
        $collection_professional_skills = $collections['professional_skills'];
        $fields_professional_skills = $collection_professional_skills->fields;
        $this->assertCount(2, $fields_professional_skills);
        // +1 : left_side (to be defined with Sandro)
        $this->assertEquals('operation_id', $fields_professional_skills[0]->field);
        $this->assertEquals('Number', $fields_professional_skills[0]->type);
        $this->assertEquals('operations.id', $fields_professional_skills[0]->reference);
        $this->assertEquals('professional_id', $fields_professional_skills[1]->field);
        $this->assertEquals('Number', $fields_professional_skills[1]->type);
        $this->assertEquals('professionals.id', $fields_professional_skills[1]->reference);
        //+ test left_side
    }

    public function tearDown()
    {

    }
}
