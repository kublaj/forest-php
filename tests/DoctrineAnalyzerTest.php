<?php

use ForestAdmin\Liana\Analyzer\DoctrineAnalyzer as DoctrineAnalyzer;
use ForestAdmin\Liana\Raw\Collection as ForestCollection;

class DoctrineAnalyzerTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var DoctrineAnalyzer
     */
    protected $da;

    /**
     * @var ForestCollection[]
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

        $this->assertCount(112, $collections);
        $firstCollection = reset($collections);
        $this->assertInstanceOf(\ForestAdmin\Liana\Model\Collection::class, $firstCollection);
    }

    public function testFieldStructure()
    {
        $collections = $this->map;
        $firstCollection = reset($collections);
        $fields = $firstCollection->getFields();
        $firstField = reset($fields);
        $this->assertInstanceOf(\ForestAdmin\Liana\Model\Field::class, $firstField);
    }

    public function testFieldTypes()
    {
        $collections = $this->map;

        // table:address: street:VARCHAR, number:INT, supplements_address:LONGTEXT, longitude:DOUBLE
        /**
         * @var \ForestAdmin\Liana\Model\Collection $collection_address
         */
        $collection_address = $collections['AppBundle\Entity\Address'];
        $fields_address = $collection_address->getFields();
        $this->assertEquals('address', $collection_address->getName());
        $this->assertEquals('AppBundle\Entity\Address', $collection_address->getEntityClassName());
        $this->assertCount(8, $fields_address);
        $this->assertEquals('street', $fields_address[1]->field);
        $this->assertEquals('String', $fields_address[1]->type);
        $this->assertEquals('number', $fields_address[2]->field);
        $this->assertEquals('Number', $fields_address[2]->type);
        $this->assertEquals('supplementsAddress', $fields_address[5]->field);
        $this->assertEquals('String', $fields_address[5]->type);
        $this->assertEquals('longitude', $fields_address[6]->field);
        $this->assertEquals('Number', $fields_address[6]->type);

        // table:asset: you_are:SMALLINT, created_at:DATETIME
        $collection_asset = $collections['AppBundle\Entity\Asset'];
        $fields_asset = $collection_asset->getFields();
        $this->assertEquals('asset', $collection_asset->getName());
        $this->assertCount(15, $fields_asset);
        $this->assertEquals('youAre', $fields_asset[7]->field);
        $this->assertEquals('Number', $fields_asset[7]->type);
        $this->assertEquals('createdAt', $fields_asset[11]->field);
        $this->assertEquals('Date', $fields_asset[11]->type);

        // table:media__media: enabled:TINYINT(1)=BOOLEAN, length:DECIMAL
        $collection_media__media = $collections['Application\Sonata\MediaBundle\Entity\Media'];
        $fields_media__media = $collection_media__media->getFields();
        $this->assertEquals('media__media', $collection_media__media->getName());
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

        $collection_professional = $collections['AppBundle\Entity\Professional'];
        $fields_professional = $collection_professional->getFields();

        /**
         * TODO : this number is obtained because of the eager loading on keys facturationpro, team, and others. Check if it should be avoided by default or not
         */
        $this->assertCount(65, $fields_professional);
        //Check primary key present
        $this->assertEquals('id', $fields_professional[38]->field);
        $this->assertEquals('Number', $fields_professional[38]->type);
        //Many-to-One
        $this->assertEquals('sponsor', $fields_professional[58]->field);
        $this->assertEquals('Number', $fields_professional[58]->type);
        $this->assertEquals('sponsor_code.id', $fields_professional[58]->reference);
        $this->assertEquals('godsons', $fields_professional[58]->inverseOf);
        //One-to-Many
        $this->assertEquals('comments', $fields_professional[53]->field);
        $this->assertTrue(is_array($fields_professional[53]->type));
        $this->assertEquals('Number', reset($fields_professional[53]->type));
        $this->assertEquals('comments.id', $fields_professional[53]->reference);
        $this->assertNull($fields_professional[53]->inverseOf);
        //Many-to-Many
        $this->assertEquals('skills', $fields_professional[55]->field);
        $this->assertTrue(is_array($fields_professional[55]->type));
        $this->assertEquals('Number', reset($fields_professional[55]->type));
        $this->assertEquals('operations.id', $fields_professional[55]->reference);
        $this->assertEquals('professionals', $fields_professional[55]->inverseOf);
        $collection_operations = $collections['AppBundle\Entity\Operation'];
        $fields_operations = $collection_operations->getFields();
        $this->assertEquals('professionals', $fields_operations[60]->field);
        $this->assertTrue(is_array($fields_operations[60]->type));
        $this->assertEquals('Number', reset($fields_operations[60]->type));
        $this->assertEquals('professionals.id', $fields_operations[60]->reference);
        $this->assertNull($fields_operations[60]->inverseOf);

        $collection_users = $collections['AppBundle\Entity\User'];
        $fields_users = $collection_users->getFields();
        $this->assertCount(50, $fields_users);
        //Check primary key present
        $this->assertEquals('id', $fields_users[38]->field);
        $this->assertEquals('Number', $fields_users[38]->type);
        //One-to-One
        $this->assertEquals('picture', $fields_users[48]->field);
        $this->assertEquals('Number', $fields_users[48]->type);
        $this->assertEquals('media__media.id', $fields_users[48]->reference);
    }

    public function testOneSidedOneToOneAssociation()
    {
        $collections = $this->map;

        $collection_sofinco = $collections['AppBundle\Entity\Sofinco'];
        $fields_sofinco = $collection_sofinco->getFields();
        $this->assertCount(5, $fields_sofinco);
        $this->assertNotNull($fields_sofinco[4]->field);
        $this->assertEquals('interested', $fields_sofinco[4]->field);

        $collection_projects = $collections['AppBundle\Entity\Project'];
        $fields_projects = $collection_projects->getFields();
        $this->assertCount(16, $fields_projects);
        $this->assertEquals('credit', $fields_projects[15]->field);
        $this->assertEquals('Number', $fields_projects[15]->type);
        $this->assertEquals('sofinco.id', $fields_projects[15]->reference);
        $this->assertEquals('project', $fields_projects[15]->inverseOf);
    }

    public function tearDown()
    {

    }
}
