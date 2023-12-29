<?php

namespace ChrisReedIO\APIAmigo\Middleware\Saloon\Request;

use Illuminate\Support\Str;
use Saloon\Contracts\RequestMiddleware;
use Saloon\Http\PendingRequest;
use Saloon\Http\Request;
use Saloon\Http\Response;

use function config;
use function dump;

class TrackRequest implements RequestMiddleware
{
    public function __invoke(PendingRequest $pendingRequest): void
    {
        dump('== API Amigo TrackRequest middleware invoked ==');

        $requestId = Str::ulid()->toBase58();
        // $headerKey = config('api-amigo.requests.header_key');
        // $pendingRequest->config()->add($headerKey, $requestId);
        $pendingRequest->config()->add('amigo.request_id', $requestId);
        $pendingRequest->config()->add('amigo.request_time', microtime(true));
        dump('Injected Amigo tracking data into request config');

        // Here we need to log the request
        // Things to track: request URL, request method, request headers, request body
        // Depending on our logging strategy, we may want to log the response as well

        // Create a new request log entry
    }
}
