<?php
namespace W2w\Laravel\Apie\Tests\Mocks;

use Illuminate\Database\Eloquent\Model;

class ModelForEloquentModelRetriever extends Model
{
    protected $table = 'status';

    protected $fillable = [
        'id',
        'value',
        'enumColumn'
    ];
}
