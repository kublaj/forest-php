<?php

class MetaTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {

    }

    public function testMetaStructure()
    {
        $meta = new \ForestAdmin\Liana\Apimap\Meta('key', 'value');

        $this->assertEquals($meta->getName(), 'key');
        $this->assertEquals($meta->getValue(), 'value');
        $meta->setName('new_key');
        $this->assertEquals($meta->getName(), 'new_key');
        $meta->setValue('new_value');
        $this->assertEquals($meta->getValue(), 'new_value');
    }

    public function tearDown()
    {

    }
}
