<?php

use ForestAdmin\Liana\Apimap\Map as Map;
use \ForestAdmin\Liana\Apimap\Meta as Meta;

class MapTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {

    }

    public function testMapStructure()
    {
        $map = new Map;
        $map->addMeta(new Meta('liana', 'forest-php'));
        $map->addMeta(new Meta('liana_version', 'blah'));
        $arrayMap = $map->toArray();

        $this->assertTrue(is_array($arrayMap));
        $this->assertArrayHasKey('data', $arrayMap);
        $this->assertArrayHasKey('meta', $arrayMap);
        $this->assertArrayHasKey('included', $arrayMap);

        $this->assertCount(2, $arrayMap['meta']);
        $this->assertArrayHasKey('liana', $arrayMap['meta']);
        $this->assertArrayHasKey('liana_version', $arrayMap['meta']);
        $this->assertEquals('forest-php', $arrayMap['meta']['liana']);
        $this->assertEquals('blah', $arrayMap['meta']['liana_version']);

    }

    public function tearDown()
    {

    }
}
