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
        $this->assertArrayHasKey('street', $fields_address);
        $this->assertEquals('street', $fields_address['street']->getField());
        $this->assertEquals('String', $fields_address['street']->getType());
        $this->assertEquals('Number', $fields_address['number']->getType());
        $this->assertEquals('String', $fields_address['supplementsAddress']->getType());
        $this->assertEquals('Number', $fields_address['longitude']->getType());

        // table:asset: you_are:SMALLINT, created_at:DATETIME
        $collection_asset = $collections['AppBundle\Entity\Asset'];
        $fields_asset = $collection_asset->getFields();
        $this->assertEquals('asset', $collection_asset->getName());
        $this->assertCount(15, $fields_asset);
        $this->assertEquals('Number', $fields_asset['youAre']->getType());
        $this->assertEquals('Date', $fields_asset['createdAt']->getType());

        // table:media__media: enabled:TINYINT(1)=BOOLEAN, length:DECIMAL
        $collection_media__media = $collections['Application\Sonata\MediaBundle\Entity\Media'];
        $fields_media__media = $collection_media__media->getFields();
        $this->assertEquals('media__media', $collection_media__media->getName());
        $this->assertCount(24, $fields_media__media);
        $this->assertEquals('Boolean', $fields_media__media['enabled']->getType());
        $this->assertEquals('Number', $fields_media__media['length']->getType());

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
        $this->assertEquals('Number', $fields_professional['id']->getType());
        $this->assertEmpty($fields_professional['id']->getReference());
        $this->assertEmpty($fields_professional['id']->getInverseOf());
        //Many-to-One
        $this->assertEquals('Number', $fields_professional['sponsor']->getType());
        $this->assertEquals('sponsor_code.id', $fields_professional['sponsor']->getReference());
        $this->assertEquals('godsons', $fields_professional['sponsor']->getInverseOf());
        //One-to-Many
        $this->assertTrue(is_array($fields_professional['comments']->getType()));
        $this->assertEquals('Number', $fields_professional['comments']->getType()[0]);
        $this->assertEquals('comments.id', $fields_professional['comments']->getReference());
        $this->assertNull($fields_professional['comments']->getInverseOf());
        //Many-to-Many
        $this->assertTrue(is_array($fields_professional['skills']->getType()));
        $this->assertEquals('Number', $fields_professional['skills']->getType()[0]);
        $this->assertEquals('operations.id', $fields_professional['skills']->getReference());
        $this->assertEquals('professionals', $fields_professional['skills']->getInverseOf());
        $collection_operations = $collections['AppBundle\Entity\Operation'];
        $fields_operations = $collection_operations->getFields();
        $this->assertTrue(is_array($fields_operations['professionals']->getType()));
        $this->assertEquals('Number', $fields_operations['professionals']->getType()[0]);
        $this->assertEquals('professionals.id', $fields_operations['professionals']->getReference());
        $this->assertNull($fields_operations['professionals']->getInverseOf());

        $collection_users = $collections['AppBundle\Entity\User'];
        $fields_users = $collection_users->getFields();
        $this->assertCount(50, $fields_users);
        //Check primary key present
        $this->assertEquals('Number', $fields_users['id']->getType());
        //One-to-One
        $this->assertEquals('picture', $fields_users['picture']->getField());
        $this->assertEquals('Number', $fields_users['picture']->getType());
        $this->assertEquals('media__media.id', $fields_users['picture']->getReference());
    }

    public function testOneSidedOneToOneAssociation()
    {
        $collections = $this->map;

        $collection_sofinco = $collections['AppBundle\Entity\Sofinco'];
        $fields_sofinco = $collection_sofinco->getFields();
        $this->assertCount(5, $fields_sofinco);
        $this->assertEquals('interested', $fields_sofinco['interested']->getField());

        $collection_projects = $collections['AppBundle\Entity\Project'];
        $fields_projects = $collection_projects->getFields();
        $this->assertCount(16, $fields_projects);
        $this->assertEquals('Number', $fields_projects['credit']->getType());
        $this->assertEquals('sofinco.id', $fields_projects['credit']->getReference());
        $this->assertEquals('project', $fields_projects['credit']->getInverseOf());
    }

    public function testBugfixEntityProfessional()
    {
        $collections = $this->map;

        $collection_professional = $collections['AppBundle\Entity\Professional'];
        $this->assertEquals('AppBundle\Entity\Professional', $collection_professional->getEntityClassName());
    }

    public function testBugfixNoArrayType()
    {
        $collections = $this->map;
        $collection_base_block = $collections['Sonata\PageBundle\Entity\BaseBlock'];
        $this->assertEquals('String', $collection_base_block->getField('settings')->getType());
    }

    public function testBugfixNoJsonType()
    {
        $collections = $this->map;
        $collection_base_user = $collections['Sonata\UserBundle\Entity\BaseUser'];
        $this->assertEquals('String', $collection_base_user->getField('twitterData')->getType());
    }

    public function tearDown()
    {

    }
}
