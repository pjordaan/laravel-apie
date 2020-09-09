<?php

namespace W2w\Laravel\Apie\Contracts;

use erasys\OpenApi\Spec\v3\Components;
use erasys\OpenApi\Spec\v3\Operation;

interface ApieMiddlewareBridgeContract
{
    public function patch(Operation  $operation, Components  $components, string $middlewareClass);
}
