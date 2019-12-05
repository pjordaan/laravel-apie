<?php
namespace W2w\Laravel\Apie\Tests\MockControllers;

use Illuminate\Routing\Controller;
use W2w\Lib\Apie\ApiResources\ApplicationInfo;
use W2w\Lib\Apie\Models\ApiResourceFacadeResponse;

class MockController extends Controller
{
    public function testApiResourceFacadeResponseList(ApiResourceFacadeResponse $response)
    {
        $resource = $response->getResource();
        return count($resource) . ' ' . get_class($resource[0]) . ' ' . strlen(json_encode($response->getNormalizedData()));
    }

    public function testApiResourceFacadeResponse(ApiResourceFacadeResponse $response)
    {
        return get_class($response->getResource()) . ' ' . strlen(json_encode($response->getNormalizedData()));
    }

    public function testResourceTypehint(ApplicationInfo $applicationInfo)
    {
        return $applicationInfo->getAppName();
    }
}
