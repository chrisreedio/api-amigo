<?php

namespace ChrisReedIO\APIAmigo\Middleware\Guzzle;

use ChrisReedIO\APIAmigo\Models\AmigoConnector;
use ChrisReedIO\APIAmigo\Models\AmigoEndpoint;
use ChrisReedIO\APIAmigo\Models\AmigoIntegration;
use ChrisReedIO\APIAmigo\Models\AmigoRequest;
use Psr\Http\Message\RequestInterface;
use Saloon\Http\SoloRequest;
use function auth;
use function class_basename;
use function get_class;
use function parse_url;

class TrackGuzzleRequest
{
    public function __invoke(RequestInterface $pendingRequest): RequestInterface
    {
        // dump('== API Amigo TrackGuzzleRequest middleware invoked ==');
        $requestId = AmigoRequest::generateId();
        $pendingRequest->withHeader('X-Api-Amigo-Request-Id', $requestId);

        $endpoint = $this->getEndpoint($pendingRequest);

        $request = $endpoint->requests()->create([
            'unique_id' => $requestId,
            'user_id' => auth()->user()?->id,
            'path' => $this->getPath($pendingRequest),
        ]);

        $request->attachRecordings();

        // dd($pendingRequest, $request->toArray());
        // Here we need to log the request
        // Things to track: request URL, request method, request headers, request body
        // Depending on our logging strategy, we may want to log the response as well
        // AmigoRequest::track($pendingRequest);
        // dump('Created AmigoRequest model:', $request->toArray());

        return $pendingRequest;
    }

    public function getEndpoint(RequestInterface $request): AmigoEndpoint
    {
        // $connector = AmigoConnector::track($pendingRequest);
        $connector = $this->getConnector($request);
        // $request = $pendingRequest->getRequest();

        // $endpointName = class_basename($request);
        // $endpointClass = get_class($request);

        // $urlParts = parse_url($pendingRequest->getUrl());
        // dd($urlParts);

        return $connector->endpoints()->firstOrCreate([
            'method' => $request->getMethod(),
            // 'name' => $request->getUri()->getPath(),
            // 'class' => $endpointClass,
            // 'path' => $urlParts['path'],
            'path' => $request->getUri()->getPath(),
        ]);
    }

    private function getConnector(RequestInterface $request): AmigoConnector
    {
        $connectorName = 'Guzzle';
        return AmigoConnector::firstOrCreate([
            // 'name' => (isset($parentClass) && $parentClass === SoloRequest::class) ? 'Solo Requests' : class_basename($saloonConnector),
            'name' => $connectorName,
            'integration_id' => $this->getIntegration($request)->id,
        ], [
            'base_url' => $request->getUri()->getHost(),
        ]);
    }

    public function getIntegration(RequestInterface $request): AmigoIntegration
    {
        $integrationName = $request->getUri()->getHost();
        return AmigoIntegration::firstOrCreate([
            'name' => $integrationName,
        ]);
    }

    private function getPath(RequestInterface $request): string
    {
        $uri = $request->getUri();
        $path = $uri->getPath();
        $query = $uri->getQuery();
        if ($query !== '') {
            $path .= '?' . $query;
        }

        return $path;
    }
}
