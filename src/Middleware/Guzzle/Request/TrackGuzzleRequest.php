<?php

namespace ChrisReedIO\APIAmigo\Middleware\Guzzle\Request;

use ChrisReedIO\APIAmigo\Models\AmigoRequest;
use Psr\Http\Message\RequestInterface;

class TrackGuzzleRequest
{
    public function __invoke(RequestInterface $request): RequestInterface
    {
        dump('== API Amigo TrackGuzzleRequest middleware invoked ==');

        // Here we need to log the request
        // Things to track: request URL, request method, request headers, request body
        // Depending on our logging strategy, we may want to log the response as well
        // AmigoRequest::track($pendingRequest);
        // dump('Created AmigoRequest model:', $request->toArray());

        return $request;
    }
}
