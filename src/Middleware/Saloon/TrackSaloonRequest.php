<?php

namespace ChrisReedIO\APIAmigo\Middleware\Saloon;

use ChrisReedIO\APIAmigo\Models\AmigoEndpoint;
use ChrisReedIO\APIAmigo\Models\AmigoRequest;
use Saloon\Contracts\RequestMiddleware;
use Saloon\Http\PendingRequest;
use function auth;
use function microtime;

class TrackSaloonRequest implements RequestMiddleware
{
    public function __invoke(PendingRequest $pendingRequest): void
    {
        // dump('== API Amigo TrackRequest middleware invoked ==');

        // Track the request
        $requestId = AmigoRequest::generateId();
        $pendingRequest->config()->add('amigo.request_id', $requestId);
        $pendingRequest->config()->add('amigo.request_time', microtime(true));

        // Find or create the endpoint
        $endpoint = AmigoEndpoint::track($pendingRequest);

        $request = $endpoint->requests()->create([
            'unique_id' => $requestId,
            'user_id' => auth()->user()?->id,
            'path' => $this->getPath($pendingRequest),
        ]);

        $request->attachRecordings();
    }

    private function getPath(PendingRequest $pendingRequest): string
    {
        $pathParts = $pendingRequest->getUri();
        $path = $pathParts->getPath();
        $query = $pathParts->getQuery();
        if ($query !== '') {
            $path .= '?' . $query;
        }

        return $path;
    }
}
