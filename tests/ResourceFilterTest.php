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
        $this->assertEquals(1, $rf->getPageNumber());

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
                    'equals' => 'ga',
                    'differs' => '!bu',
                    'contains' => '*zo*',
                    'starts' => 'meu*',
                    'ends' => '*ga',
                    'greater' => '>1',
                    'lower' => '<1',
                    'present' => '$present',
                    'blank' => '$blank',
                )
            )
        );

        $this->assertTrue($rf->hasFilters());
        $this->assertCount(9, $rf->getFilters());
        $this->assertNull($rf->getFilter('plok'));
        $this->assertTrue($rf->getFilter('equals')->isEqual());
        $this->assertTrue($rf->getFilter('differs')->isDifferent());
        $this->assertTrue($rf->getFilter('contains')->isContains());
        $this->assertTrue($rf->getFilter('starts')->isStartsBy());
        $this->assertTrue($rf->getFilter('ends')->isEndsBy());
        $this->assertTrue($rf->getFilter('greater')->isGreaterThan());
        $this->assertTrue($rf->getFilter('lower')->isLowerThan());
        $this->assertTrue($rf->getFilter('present')->isPresent());
        $this->assertTrue($rf->getFilter('blank')->isBlank());
        $this->assertFalse($rf->getFilter('differs')->isEqual());
        $this->assertFalse($rf->getFilter('contains')->isDifferent());
        $this->assertFalse($rf->getFilter('starts')->isContains());
        $this->assertFalse($rf->getFilter('ends')->isStartsBy());
        $this->assertFalse($rf->getFilter('greater')->isEndsBy());
        $this->assertFalse($rf->getFilter('lower')->isGreaterThan());
        $this->assertFalse($rf->getFilter('present')->isLowerThan());
        $this->assertFalse($rf->getFilter('blank')->isPresent());
        $this->assertFalse($rf->getFilter('equals')->isBlank());

        $rf = new ResourceFilter(array('sort' => '-'));
        $this->assertFalse($rf->hasFilters());
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
