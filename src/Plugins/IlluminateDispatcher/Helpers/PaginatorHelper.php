<?php

namespace W2w\Laravel\Apie\Plugins\IlluminateDispatcher\Helpers;

use Generator;
use Pagerfanta\Adapter\AdapterInterface;
use Pagerfanta\Pagerfanta;
use W2w\Laravel\Apie\Plugins\IlluminateDispatcher\Pagers\FilteredArrayPager;
use W2w\Laravel\Apie\Plugins\IlluminateDispatcher\Pagers\PagerfantaPager;

class PaginatorHelper
{
    public static function convertToPaginator(iterable $object, callable $filterFn): Pagerfanta
    {
        $pager = new Pagerfanta(self::toAdapter($object, $filterFn));
        $pager->setAllowOutOfRangePages(true);
        return $pager;
    }

    private static function toAdapter(iterable $object, callable $filterFn): AdapterInterface
    {
        if ($object instanceof Pagerfanta) {
            return new PagerfantaPager($object, $filterFn);
        }
        if (is_array($object)) {
            return new FilteredArrayPager($object, $filterFn);
        }
        return new FilteredArrayPager(iterator_to_array(self::iterate($object)), $filterFn);
    }

    private static function iterate(iterable $object): Generator
    {
        foreach ($object as $value) {
            yield $value;
        }
    }
}
