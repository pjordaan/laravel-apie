<?php

namespace W2w\Laravel\Apie\Plugins\IlluminateDispatcher\Pagers;

use Pagerfanta\Adapter\AdapterInterface;
use Pagerfanta\Pagerfanta;

class PagerfantaPager implements AdapterInterface
{
    /**
     * @var Pagerfanta
     */
    private $pagerfanta;

    /**
     * @var callable
     */
    private $filterFn;

    public function __construct(Pagerfanta $pagerfanta, callable $filterFn)
    {
        $this->pagerfanta = $pagerfanta;
        $pagerfanta->setAllowOutOfRangePages(true);
        $this->filterFn = $filterFn;
    }

    public function getNbResults()
    {
        return $this->pagerfanta->getNbResults();
    }

    public function getSlice($offset, $length)
    {
        $this->pagerfanta->setCurrentPage(1 + $offset / $length);
        $this->pagerfanta->setMaxPerPage($length);
        $result = [];
        foreach ($this->pagerfanta as $resource) {
            if (call_user_func($this->filterFn, $resource)) {
                $result[] = $resource;
            }
        }
        return $result;
    }
}
