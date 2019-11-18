<?php
namespace W2w\Laravel\Apie\Tests\Mocks;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class PolicyServiceProvider extends ServiceProvider
{
    /**
     * Allow any resource action.
     *
     * @return void
     */
    public function boot()
    {
        Gate::before(
            function () {
                return new Response();
            }
        );
    }
}
