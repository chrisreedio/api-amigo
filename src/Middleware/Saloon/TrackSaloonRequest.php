<?php

namespace ChrisReedIO\APIAmigo\Middleware\Saloon;

use ChrisReedIO\APIAmigo\Models\AmigoRequest;
use Saloon\Contracts\RequestMiddleware;
use Saloon\Http\PendingRequest;

class TrackSaloonRequest implements RequestMiddleware
{
    public function __invoke(PendingRequest $pendingRequest): void
    {
        AmigoRequest::track($pendingRequest);
    }
}
