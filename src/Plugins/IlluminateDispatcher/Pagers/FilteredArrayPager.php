<?php

namespace W2w\Laravel\Apie\Plugins\IlluminateDispatcher\Pagers;

use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Pagerfanta;

class FilteredArrayPager extends PagerfantaPager
{
    public function __construct(array $list, callable $filterFn)
    {
        parent::__construct(new Pagerfanta(new ArrayAdapter($list)), $filterFn);
    }
}
