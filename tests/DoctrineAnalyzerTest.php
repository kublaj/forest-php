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
        $this->assertEquals('street', $fields_address[1]->getField());
        $this->assertEquals('String', $fields_address[1]->getType());
        $this->assertEquals('number', $fields_address[2]->getField());
        $this->assertEquals('Number', $fields_address[2]->getType());
        $this->assertEquals('supplementsAddress', $fields_address[5]->getField());
        $this->assertEquals('String', $fields_address[5]->getType());
        $this->assertEquals('longitude', $fields_address[6]->getField());
        $this->assertEquals('Number', $fields_address[6]->getType());

        // table:asset: you_are:SMALLINT, created_at:DATETIME
        $collection_asset = $collections['AppBundle\Entity\Asset'];
        $fields_asset = $collection_asset->getFields();
        $this->assertEquals('asset', $collection_asset->getName());
        $this->assertCount(15, $fields_asset);
        $this->assertEquals('youAre', $fields_asset[7]->getField());
        $this->assertEquals('Number', $fields_asset[7]->getType());
        $this->assertEquals('createdAt', $fields_asset[11]->getField());
        $this->assertEquals('Date', $fields_asset[11]->getType());

        // table:media__media: enabled:TINYINT(1)=BOOLEAN, length:DECIMAL
        $collection_media__media = $collections['Application\Sonata\MediaBundle\Entity\Media'];
        $fields_media__media = $collection_media__media->getFields();
        $this->assertEquals('media__media', $collection_media__media->getName());
        $this->assertCount(24, $fields_media__media);
        $this->assertEquals('enabled', $fields_media__media[2]->getField());
        $this->assertEquals('Boolean', $fields_media__media[2]->getType());
        $this->assertEquals('length', $fields_media__media[9]->getField());
        $this->assertEquals('Number', $fields_media__media[9]->getType());

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
        $this->assertEquals('id', $fields_professional[38]->getField());
        $this->assertEquals('Number', $fields_professional[38]->getType());
        //Many-to-One
        $this->assertEquals('sponsor', $fields_professional[58]->getField());
        $this->assertEquals('Number', $fields_professional[58]->getType());
        $this->assertEquals('sponsor_code.id', $fields_professional[58]->getReference());
        $this->assertEquals('godsons', $fields_professional[58]->getInverseOf());
        //One-to-Many
        $this->assertEquals('comments', $fields_professional[53]->getField());
        $this->assertTrue(is_array($fields_professional[53]->getType()));
        $this->assertEquals('Number', $fields_professional[53]->getType()[0]);
        $this->assertEquals('comments.id', $fields_professional[53]->getReference());
        $this->assertNull($fields_professional[53]->getInverseOf());
        //Many-to-Many
        $this->assertEquals('skills', $fields_professional[55]->getField());
        $this->assertTrue(is_array($fields_professional[55]->getType()));
        $this->assertEquals('Number', $fields_professional[55]->getType()[0]);
        $this->assertEquals('operations.id', $fields_professional[55]->getReference());
        $this->assertEquals('professionals', $fields_professional[55]->getInverseOf());
        $collection_operations = $collections['AppBundle\Entity\Operation'];
        $fields_operations = $collection_operations->getFields();
        $this->assertEquals('professionals', $fields_operations[60]->getField());
        $this->assertTrue(is_array($fields_operations[60]->getType()));
        $this->assertEquals('Number', $fields_operations[60]->getType()[0]);
        $this->assertEquals('professionals.id', $fields_operations[60]->getReference());
        $this->assertNull($fields_operations[60]->getInverseOf());

        $collection_users = $collections['AppBundle\Entity\User'];
        $fields_users = $collection_users->getFields();
        $this->assertCount(50, $fields_users);
        //Check primary key present
        $this->assertEquals('id', $fields_users[38]->getField());
        $this->assertEquals('Number', $fields_users[38]->getType());
        //One-to-One
        $this->assertEquals('picture', $fields_users[48]->getField());
        $this->assertEquals('Number', $fields_users[48]->getType());
        $this->assertEquals('media__media.id', $fields_users[48]->getReference());
    }

    public function testOneSidedOneToOneAssociation()
    {
        $collections = $this->map;

        $collection_sofinco = $collections['AppBundle\Entity\Sofinco'];
        $fields_sofinco = $collection_sofinco->getFields();
        $this->assertCount(5, $fields_sofinco);
        $this->assertNotNull($fields_sofinco[4]->getField());
        $this->assertEquals('interested', $fields_sofinco[4]->getField());

        $collection_projects = $collections['AppBundle\Entity\Project'];
        $fields_projects = $collection_projects->getFields();
        $this->assertCount(16, $fields_projects);
        $this->assertEquals('credit', $fields_projects[15]->getField());
        $this->assertEquals('Number', $fields_projects[15]->getType());
        $this->assertEquals('sofinco.id', $fields_projects[15]->getReference());
        $this->assertEquals('project', $fields_projects[15]->getInverseOf());
    }

    public function testBugfixEntityProfessional()
    {
        $collections = $this->map;

        $collection_professional = $collections['AppBundle\Entity\Professional'];
        $this->assertEquals('AppBundle\Entity\Professional', $collection_professional->getEntityClassName());
    }

    public function tearDown()
    {

    }
}
