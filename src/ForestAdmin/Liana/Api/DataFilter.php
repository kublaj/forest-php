<?php

namespace ForestAdmin\Liana\Api;


class DataFilter
{
    const FILTER_EQ = 0;
    const FILTER_NEQ = 1;
    const FILTER_STARTS_BY = 2;
    const FILTER_ENDS_BY = 3;
    const FILTER_CONTAINS = 4;
    const FILTER_GT = 5;
    const FILTER_LT = 6;
    const FILTER_PRESENT = 7;
    const FILTER_BLANK = 8;

    /**
     * @var string
     */
    protected $fieldName;

    /**
     * @var string
     */
    protected $filterString;

    /**
     * @var int
     */
    protected $filterType = self::FILTER_EQ;

    public function __construct($fieldName, $filterString)
    {
        $this->setFieldName($fieldName);
        $this->setFilterString($filterString);
    }

    /**
     * @param string $fieldName
     */
    public function setFieldName($fieldName)
    {
        $this->fieldName = $fieldName;
    }

    /**
     * @return string
     */
    public function getFieldName()
    {
        return $this->fieldName;
    }

    /**
     * @param string $filterString
     */
    public function setFilterString($filterString)
    {
        if (preg_match('/^!/', $filterString)) {
            $filterString = preg_replace('/^!/', '', $filterString);
            $this->setFilterType(self::FILTER_NEQ);
        } elseif (preg_match('/^>/', $filterString)) {
            $filterString = preg_replace('/^>/', '', $filterString);
            $this->setFilterType(self::FILTER_GT);
        } elseif (preg_match('/^</', $filterString)) {
            $filterString = preg_replace('/^</', '', $filterString);
            $this->setFilterType(self::FILTER_LT);
        } elseif (preg_match('/^\*/', $filterString) && preg_match('/\*$/', $filterString)) {
            $filterString = preg_replace('/^\*/', '', $filterString);
            $filterString = preg_replace('/\*$/', '', $filterString);
            $this->setFilterType(self::FILTER_CONTAINS);
        } elseif (preg_match('/\*$/', $filterString)) {
            $filterString = preg_replace('/\*$/', '', $filterString);
            $this->setFilterType(self::FILTER_STARTS_BY);
        } elseif (preg_match('/^\*/', $filterString)) {
            $filterString = preg_replace('/^\*/', '', $filterString);
            $this->setFilterType(self::FILTER_ENDS_BY);
        } elseif ($filterString == '$present') {
            $filterString = '';
            $this->setFilterType(self::FILTER_PRESENT);
        } elseif ($filterString == '$blank') {
            $filterString = '';
            $this->setFilterType(self::FILTER_BLANK);
        } else {
            $this->setFilterType(self::FILTER_EQ);
        }
        
        $this->filterString = $filterString;
    }

    /**
     * @return string
     */
    public function getFilterString()
    {
        return $this->filterString;
    }

    /**
     * @param int $filterType
     */
    public function setFilterType($filterType)
    {
        $this->filterType = $filterType;
    }

    /**
     * @return int
     */
    public function getFilterType()
    {
        return $this->filterType;
    }

    /**
     * @return bool
     */
    public function isEqual()
    {
        return $this->getFilterType() == self::FILTER_EQ;
    }

    /**
     * @return bool
     */
    public function isDifferent()
    {
        return $this->getFilterType() == self::FILTER_NEQ;
    }

    /**
     * @return bool
     */
    public function isContains()
    {
        return $this->getFilterType() == self::FILTER_CONTAINS;
    }

    /**
     * @return bool
     */
    public function isStartsBy()
    {
        return $this->getFilterType() == self::FILTER_STARTS_BY;
    }

    /**
     * @return bool
     */
    public function isEndsBy()
    {
        return $this->getFilterType() == self::FILTER_ENDS_BY;
    }

    /**
     * @return bool
     */
    public function isGreaterThan()
    {
        return $this->getFilterType() == self::FILTER_GT;
    }

    /**
     * @return bool
     */
    public function isLowerThan()
    {
        return $this->getFilterType() == self::FILTER_LT;
    }

    /**
     * @return bool
     */
    public function isPresent()
    {
        return $this->getFilterType() == self::FILTER_PRESENT;
    }

    /**
     * @return bool
     */
    public function isBlank()
    {
        return $this->getFilterType() == self::FILTER_BLANK;
    }
}