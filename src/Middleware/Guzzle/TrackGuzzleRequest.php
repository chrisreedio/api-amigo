<?php

namespace ChrisReedIO\APIAmigo\Middleware\Guzzle;

use ChrisReedIO\APIAmigo\Models\AmigoConnector;
use ChrisReedIO\APIAmigo\Models\AmigoEndpoint;
use ChrisReedIO\APIAmigo\Models\AmigoIntegration;
use ChrisReedIO\APIAmigo\Models\AmigoRecording;
use ChrisReedIO\APIAmigo\Models\AmigoRequest;
use Psr\Http\Message\RequestInterface;

use function auth;

class TrackGuzzleRequest
{
    // public function __invoke(RequestInterface $pendingRequest): RequestInterface
    public function __invoke(callable $handler)
    {
        return function (RequestInterface $pendingRequest, array $options) use ($handler) {
            // dump('== API Amigo TrackGuzzleRequest middleware invoked ==');
            $requestId = AmigoRequest::generateId();
            $options['amigo']['request_id'] = $requestId;
            $options['amigo']['request_time'] = microtime(true);
            $options['amigo']['method'] = $pendingRequest->getMethod();
            // $options['amigo']['base_uri'] = $pendingRequest->getUri()->__toString());
            // $pendingRequest->withHeader('X-Api-Amigo-Request-Id', $requestId);

            $endpoint = $this->getEndpoint($pendingRequest);

            $recordings = AmigoRecording::activeFor($endpoint->connector_id);
            $capturePayload = AmigoRequest::capturesPayload($recordings);

            $request = $endpoint->requests()->create([
                'unique_id' => $requestId,
                'user_id' => auth()->user()?->getAuthIdentifier(),
                'path' => $this->getPath($pendingRequest),
                ...($capturePayload ? AmigoRequest::psrPayload($pendingRequest) : []),
            ]);
            $request->attachRecordings($recordings);

            // Passed to TrackGuzzleResponse so it doesn't look these up again.
            $options['amigo']['request_key'] = $request->id;
            $options['amigo']['endpoint_id'] = $endpoint->id;
            $options['amigo']['capture_payload'] = $capturePayload;

            return $handler($pendingRequest, $options);
            // return $pendingRequest;
        };
    }

    private function getEndpoint(RequestInterface $request): AmigoEndpoint
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

        $connectorName = $request->getUri()->getHost() . ' via Guzzle';

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
