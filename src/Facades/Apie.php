<?php
namespace W2w\Laravel\Apie\Facades;

use Illuminate\Support\Facades\Facade;
use W2w\Lib\Apie\ApiResourceFacade;

class Apie extends Facade
{
    protected static function getFacadeAccessor() {
        return ApiResourceFacade::class;
    }
}
