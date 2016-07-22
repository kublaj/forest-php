<?php

namespace ForestAdmin\Liana\Analyzer;


use ForestAdmin\Liana\Model\Collection;

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