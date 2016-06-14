<?php
use \ForestAdmin\Liana\Apimap\Entity as Entity;

class EntityTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {

    }

    public function testEntityStructure()
    {
        $entity = new Entity();
        $arrayEntity = $entity->toArray();

        $this->assertTrue(is_array($arrayEntity));
        $this->assertArrayHasKey('id', $arrayEntity);
        $this->assertArrayHasKey('type', $arrayEntity);
        $this->assertArrayHasKey('attributes', $arrayEntity);
        $this->assertArrayHasKey('links', $arrayEntity);
        $this->assertArrayHasKey('relationships', $arrayEntity);
    }

    public function tearDown()
    {

    }
}
