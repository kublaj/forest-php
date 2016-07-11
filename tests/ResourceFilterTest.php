<?php

use ForestAdmin\Liana\Api\ResourceFilter;

class ResourceFilterTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {

    }

    public function testPageSize()
    {
        $rf = new ResourceFilter(array('page' => array('size' => 1)));
        $this->assertTrue($rf->hasPageSize());
        $this->assertEquals(1, $rf->getPageSize());

        $rf = new ResourceFilter(array('page' => array('size' => 'plok')));
        $this->assertTrue($rf->hasPageSize());
        $this->assertEquals(0, $rf->getPageSize());

        $rf = new ResourceFilter(array());
        $this->assertFalse($rf->hasPageSize());
        $this->assertNull($rf->getPageSize());
    }

    public function testPageNumber()
    {
        $rf = new ResourceFilter(array('page' => array('number' => 1)));
        $this->assertTrue($rf->hasPageNumber());
        $this->assertEquals(1, $rf->getPageNumber());

        $rf = new ResourceFilter(array('page' => array('number' => 'plok')));
        $this->assertTrue($rf->hasPageNumber());
        $this->assertEquals(0, $rf->getPageNumber());

        $rf = new ResourceFilter(array());
        $this->assertFalse($rf->hasPageNumber());
        $this->assertNull($rf->getPageNumber());
    }

    public function testSort()
    {
        $rf = new ResourceFilter(array('sort' => 'plok'));
        $this->assertTrue($rf->hasSortBy());
        $this->assertEquals('ASC', $rf->getSortOrder());
        $this->assertEquals('plok', $rf->getSortBy());

        $rf = new ResourceFilter(array('sort' => '-foobar'));
        $this->assertTrue($rf->hasSortBy());
        $this->assertEquals('DESC', $rf->getSortOrder());
        $this->assertEquals('foobar', $rf->getSortBy());

        $rf = new ResourceFilter(array('sort' => '-'));
        $this->assertFalse($rf->hasSortBy());
        $this->assertEquals('ASC', $rf->getSortOrder());
        $this->assertNull($rf->getSortBy());
    }

    public function testFilter()
    {
        $rf = new ResourceFilter(
            array('filter' => array(
                    'fullfield' => 'ga',
                    'leading' => 'bu*',
                    'trailing' => '*zo',
                )
            )
        );
        $this->assertTrue($rf->hasFilter());
        $this->assertCount(3, $rf->getFilter());

        $rf = new ResourceFilter(array('sort' => '-'));
        $this->assertFalse($rf->hasFilter());
    }

    public function testSearch()
    {
        $rf = new ResourceFilter(
            array('search' => 3)
        );
        $this->assertTrue($rf->hasSearch());
        $this->assertEquals(3, $rf->getSearch());
    }

    public function tearDown()
    {

    }
}
