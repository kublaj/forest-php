<?php
/**
 * Created by PhpStorm.
 * User: jean-marc
 * Date: 22/06/16
 * Time: 13:39
 */

namespace ForestAdmin\Liana\Analyzer;


use ForestAdmin\Liana\Raw\Collection;

interface OrmAnalyzer
{
    /**
     * @return Collection[]
     */
    public function analyze();

    /**
     * @param object $em
     */
    public function setEntityManager($em);

    /**
     * @return object
     */
    public function getEntityManager();

    /**
     * @param array $data
     */
    public function setMetadata($data);

    /**
     * @return array
     */
    public function getMetadata();
}