<?php

use ForestAdmin\Liana\Apimap\Field as Field;

class FieldTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Field
     */
    protected $fieldObject;

    public function setUp()
    {
        $this->fieldObject = new Field;
        $this->fieldObject->setField('firstname');
        $this->fieldObject->setType('String');
        $this->fieldObject->setReference('firstnames.firstname');
    }

    public function testFieldStructure()
    {
        $this->assertEquals('firstname', $this->fieldObject->getField());
        $this->assertEquals('String', $this->fieldObject->getType());
        $this->assertEquals('firstnames.firstname', $this->fieldObject->getReference());
    }

    /**
     * @depends testFieldStructure
     */
    public function testFieldArray()
    {
        $arrayField = $this->fieldObject->toArray();

        $this->assertTrue(is_array($arrayField));

        $this->assertArrayHasKey('field', $arrayField);
        $this->assertEquals('firstname', $arrayField['field']);

        $this->assertArrayHasKey('type', $arrayField);
        $this->assertEquals('String', $arrayField['type']);

        $this->assertArrayHasKey('reference', $arrayField);
        $this->assertEquals('firstnames.firstname', $arrayField['reference']);
    }

    public function tearDown()
    {

    }
}
