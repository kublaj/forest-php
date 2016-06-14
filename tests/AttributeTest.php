<?php

use ForestAdmin\Liana\Apimap\Attribute as Attribute;

class AttributeTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {

    }

    public function testAttributeStructure()
    {
        $attribute = new Attribute;

        $attribute->setName('employees');
        $this->assertEquals('employees', $attribute->getName());

        $this->assertTrue(is_array($attribute->getFields()));
        $this->assertCount(0, $attribute->getFields());

        $this->assertFalse($attribute->isOnlyForRelationships());
        $this->assertFalse($attribute->isVirtual());
        $this->assertFalse($attribute->isReadOnly());
        $this->assertFalse($attribute->isSearchable());
        $attribute->setOnlyForRelationships();
        $attribute->setVirtual();
        $attribute->setReadOnly();
        $attribute->setSearchable();
        $this->assertTrue($attribute->isOnlyForRelationships());
        $this->assertTrue($attribute->isVirtual());
        $this->assertTrue($attribute->isReadOnly());
        $this->assertTrue($attribute->isSearchable());
        $attribute->setOnlyForRelationships(false);
        $attribute->setVirtual(0);
        $attribute->setReadOnly(null);
        $attribute->setSearchable('');
        $this->assertFalse($attribute->isOnlyForRelationships());
        $this->assertFalse($attribute->isVirtual());
        $this->assertFalse($attribute->isReadOnly());
        $this->assertFalse($attribute->isSearchable());
    }

    public function testAttributeArray()
    {
        $attribute = new Attribute;
        $attribute->setName('employees');
        $attribute->setOnlyForRelationships();
        $attribute->setVirtual();
        $attribute->setReadOnly();
        $attribute->setSearchable();
        $arrayAttribute = $attribute->toArray();

        $this->assertTrue(is_array($arrayAttribute));

        $this->assertArrayHasKey('name', $arrayAttribute);
        $this->assertEquals('employees', $arrayAttribute['name']);

        $this->assertArrayHasKey('fields', $arrayAttribute);
        $this->assertTrue(is_array($arrayAttribute['fields']));
        $this->assertCount(0, $arrayAttribute['fields']);

        $this->assertArrayHasKey('only_for_relationships', $arrayAttribute);
        $this->assertTrue($arrayAttribute['only_for_relationships']);

        $this->assertArrayHasKey('is_virtual', $arrayAttribute);
        $this->assertTrue($arrayAttribute['is_virtual']);

        $this->assertArrayHasKey('is_read_only', $arrayAttribute);
        $this->assertTrue($arrayAttribute['is_read_only']);

        $this->assertArrayHasKey('is_searchable', $arrayAttribute);
        $this->assertTrue($arrayAttribute['is_searchable']);
    }

    public function tearDown()
    {

    }
}
