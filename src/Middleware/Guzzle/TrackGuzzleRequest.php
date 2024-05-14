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

        return $pendingRequest;
    }

    public function getEndpoint(RequestInterface $request): AmigoEndpoint
    {
        $connector = $this->getConnector($request);

        return $connector->endpoints()->firstOrCreate([
            'method' => $request->getMethod(),
            'path' => $request->getUri()->getPath(),
        ]);
    }

    private function getConnector(RequestInterface $request): AmigoConnector
    {
        $integration = AmigoIntegration::firstOrCreate([
            'name' => $request->getUri()->getHost(),
        ]);

        $connectorName = 'Guzzle';
        return AmigoConnector::firstOrCreate([
            'name' => $connectorName,
            'integration_id' => $integration->id,
        ], [
            'base_url' => $request->getUri()->getHost(),
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
