<?php

use ForestAdmin\Liana\Analyzer\DoctrineAnalyzer as DoctrineAnalyzer;

class DoctrineAnalyzerTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var DoctrineAnalyzer
     */
    protected $da;

    protected $map;

    public function setUp()
    {
        $config = new \Doctrine\DBAL\Configuration;
        $this->da = new DoctrineAnalyzer;
        $this->da
            ->setDatabaseUrl('mysql://root:secret@localhost:33060/drapo');

        $this->map = $this->da->analyze();
        $this->assertTrue(is_array($this->map));
    }

    public function testData()
    {
        $map = $this->map;

        $this->assertArrayHasKey('data', $map);

    }

    public function testMeta()
    {
        $map = $this->map;

        $this->assertArrayHasKey('meta', $map);
        $this->assertArrayHasKey('liana', $map['meta']);
        $this->assertArrayHasKey('liana-version', $map['meta']);
    }

    public function tearDown()
    {

    }
}
