<?php

namespace ChrisReedIO\APIAmigo\Middleware\Saloon;

use ChrisReedIO\APIAmigo\Models\AmigoRequest;
use Saloon\Contracts\RequestMiddleware;
use Saloon\Http\PendingRequest;

class TrackSaloonRequest implements RequestMiddleware
{
    public function __invoke(PendingRequest $pendingRequest): void
    {
        // dump('== API Amigo TrackRequest middleware invoked ==');

        // Here we need to log the request
        // Things to track: request URL, request method, request headers, request body
        // Depending on our logging strategy, we may want to log the response as well
        AmigoRequest::track($pendingRequest);
        // dump('Created AmigoRequest model:', $request->toArray());
    }
}
