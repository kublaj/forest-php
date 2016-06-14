<?php

use ForestAdmin\Liana\Apimap\Map as Map;

class MapTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {

    }

    public function testMapStructure()
    {
        $map = new Map;
        $arrayMap = $map->toArray();

        $this->assertTrue(is_array($arrayMap));
        $this->assertArrayHasKey('data', $arrayMap);
        $this->assertArrayHasKey('meta', $arrayMap);
        $this->assertArrayHasKey('included', $arrayMap);
    }

    public function tearDown()
    {

    }
}
