<?php
/**
 * Created by PhpStorm.
 * User: jean-marc
 * Date: 21/06/16
 * Time: 16:45
 */

namespace ForestAdmin\Liana\Api;


class ResourceFilter
{
    /**
     * @var integer|null
     */
    protected $pageSize = null;

    /**
     * @var integer|null
     */
    protected $pageNumber = null;

    /**
     * @var string|null
     */
    protected $sortBy = null;

    /**
     * @var string|null
     */
    protected $sortOrder = 'ASC';

    /**
     * @var string|null
     */
    protected $search = null;

    /**
     * @var DataFilter[]
     */
    protected $filters;

    public function __construct($filterArray)
    {
        if (array_key_exists('page', $filterArray)) {
            if (array_key_exists('number', $filterArray['page'])) {
                $this->setPageNumber(intval($filterArray['page']['number']));
            }
            if (array_key_exists('size', $filterArray['page'])) {
                $this->setPageSize(intval($filterArray['page']['size']));
            }
        }

        if(array_key_exists('sort', $filterArray)) {
            $this->setSort($filterArray['sort']);
        }

        if(array_key_exists('search', $filterArray)) {
            $this->setSearch($filterArray['search']);
        }

        $this->filters = array();
        if(array_key_exists('filter', $filterArray)) {
            $this->setFilters($filterArray['filter']);
        }
    }

    /**
     * @param int $pageNumber
     */
    public function setPageNumber($pageNumber = 1)
    {
        $pageNumber = intval($pageNumber);
        $this->pageNumber = $pageNumber < 1 ? 1 : $pageNumber;
    }

    /**
     * @param int $pageSize
     */
    public function setPageSize($pageSize)
    {
        $this->pageSize = $pageSize;
    }

    /**
     * @param string $sortBy
     */
    public function setSortBy($sortBy)
    {
        $this->sortBy = $sortBy;
    }

    /**
     * @param string $sortOrder
     */
    public function setSortOrder($sortOrder = 'ASC')
    {
        $sortOrder = strtoupper($sortOrder);

        if($sortOrder != 'ASC' && $sortOrder != 'DESC') {
            $sortOrder = 'DESC';
        }

        $this->sortOrder = $sortOrder;
    }

    public function setSort($sortString)
    {
        if(!empty($sortString) && $sortString[0] == '-') {
            $this->setSortOrder('DESC');
            $sortString = substr($sortString, 1);
        }

        if($sortString) {
            $this->setSortBy($sortString);
        } else {
            $this->setSortOrder();
        }
    }

    /**
     * @return null|string
     */
    public function getSortBy()
    {
        return $this->sortBy;
    }

    /**
     * @return null|string
     */
    public function getSortOrder()
    {
        return $this->sortOrder;
    }

    /**
     * @param array $filters
     */
    public function setFilters($filters)
    {
        $this->filters = array();

        foreach($filters as $fieldName => $value) {
            $this->filters[$fieldName] = new DataFilter($fieldName, $value);
        };
    }

    /**
     * @param null|string $search
     */
    public function setSearch($search)
    {
        $this->search = $search;
    }

    /**
     * @return int|null
     */
    public function getPageSize()
    {
        return $this->pageSize;
    }

    /**
     * @return int|null
     */
    public function getPageNumber()
    {
        return $this->pageNumber;
    }

    /**
     * @return null|string
     */
    public function getSort()
    {
        return $this->sort;
    }

    /**
     * @return null|string
     */
    public function getSearch()
    {
        return $this->search;
    }

    /**
     * @return DataFilter[]null
     */
    public function getFilters()
    {
        return $this->filters;
    }
    
    /**
     * @param string $fieldName
     * @return DataFilter|null
     */
    public function getFilter($fieldName)
    {
        if($this->hasFilter($fieldName)) {
            return $this->filters[$fieldName];
        }
        
        return null;
    }

    public function hasPageNumber()
    {
        return !is_null($this->getPageNumber());
    }

    public function hasPageSize()
    {
        return !is_null($this->getPageSize());
    }

    public function hasSearch()
    {
        return !is_null($this->getSearch());
    }

    public function hasSortBy()
    {
        return !is_null($this->getSortBy());
    }

    public function hasFilters()
    {
        return 0 < count($this->getFilters());
    }
    
    public function hasFilter($fieldName)
    {
        return $fieldName && is_array($this->getFilters()) && array_key_exists($fieldName, $this->getFilters());
    }
}