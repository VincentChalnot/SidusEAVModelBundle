<?php

namespace Sidus\EAVModelBundle\Configuration;

use Sidus\EAVModelBundle\Filter\FilterInterface;

class FilterConfigurationHandler
{
    /** @var FilterInterface[] */
    protected $filters;

    /**
     * @param FilterInterface $filter
     */
    public function addFilter(FilterInterface $filter)
    {
        $this->filters[$filter->getName()] = $filter;
    }

    /**
     * @return FilterInterface[]
     */
    public function getFilters()
    {
        return $this->filters;
    }

    /**
     * @param $code
     * @return FilterInterface|null
     */
    public function getFilter($code)
    {
        if (empty($this->filters[$code])) {
            return null;
        }
        return $this->filters[$code];
    }
}