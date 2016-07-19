<?php

namespace ForestAdmin\Liana\Model;


class Pivot
{
    protected $intermediaryTableName = null;
    protected $sourceIdentifier = null;
    protected $targetIdentifier = null;

    public function __construct($sourceIdentifier, $targetIdentifier = null, $intermediaryTableName = null)
    {
        $this->setSourceIdentifier($sourceIdentifier);
        $this->setTargetIdentifier($targetIdentifier);
        $this->setIntermediaryTableName($intermediaryTableName);
    }

    /**
     * If left to null, the object indicates the foreign key of a One relationship through $sourceIdentifier
     * @return mixed
     */
    public function getIntermediaryTableName()
    {
        return $this->intermediaryTableName;
    }

    /**
     * @param mixed $intermediaryTableName
     */
    public function setIntermediaryTableName($intermediaryTableName)
    {
        $this->intermediaryTableName = $intermediaryTableName;
    }

    /**
     * @return mixed
     */
    public function getSourceIdentifier()
    {
        return $this->sourceIdentifier;
    }

    /**
     * @param mixed $sourceIdentifier
     */
    public function setSourceIdentifier($sourceIdentifier)
    {
        $this->sourceIdentifier = $sourceIdentifier;
    }

    /**
     * @return mixed
     */
    public function getTargetIdentifier()
    {
        return $this->targetIdentifier;
    }

    /**
     * @param mixed $targetIdentifier
     */
    public function setTargetIdentifier($targetIdentifier)
    {
        $this->targetIdentifier = $targetIdentifier;
    }
}