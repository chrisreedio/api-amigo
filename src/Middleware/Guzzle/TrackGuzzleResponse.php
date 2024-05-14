<?php

namespace ChrisReedIO\APIAmigo\Middleware\Guzzle;

use ChrisReedIO\APIAmigo\Models\AmigoConnector;
use ChrisReedIO\APIAmigo\Models\AmigoEndpoint;
use ChrisReedIO\APIAmigo\Models\AmigoIntegration;
use ChrisReedIO\APIAmigo\Models\AmigoRequest;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use function config;
use function trim;

class TrackGuzzleResponse
{
    public function __invoke(ResponseInterface $pendingResponse): ResponseInterface
    {
        dump('== API Amigo LogGuzzleResponse middleware invoked ==');

        // AmigoResponse::track($response);
        // dd($pendingResponse->getBody()->getContents());
        dd($pendingResponse->getHeaders());

        // TODO: Track Rate Limit Headers

        $endpoint = AmigoEndpoint::track($saloonResponse->getPendingRequest());

        // Update latest rate limit information
        $endpoint->connector->rate_limit = $rateLimit;
        $endpoint->connector->rate_limit_remaining = $rateLimitRemaining;
        $endpoint->connector->save();

        // Find the request that this response belongs to (if any)
        $request = AmigoRequest::where('unique_id', $amigoRequestId)->first();

        // Should we log the response body?
        // Do we have an active recording that wants to capture the body?
        $recordBody = $request?->recordings()->active()->where('capture_body', true)->count() > 0;
        // Capture all bodies based on config
        if (! $recordBody) {
            $recordBody = config('api-amigo.responses.capture_body');
        }
        // Capture Error bodies if enabled
        if (! $recordBody) {
            $recordBody = config('api-amigo.responses.capture_body_on_error') && $saloonResponse->failed();
        }

        // dd("'" . $saloonResponse->body() . "'");

        $response = $endpoint->responses()->create([
            'request_id' => $request?->id,
            'status_code' => $saloonResponse->status(),
            // 'status_message' => $response->status(),
            'headers' => $saloonResponse->headers()->all(),
            'body' => $recordBody ? trim($pendingResponse->getBody()->getContents()) : null,
            'request_unique_id' => $amigoRequestId,
            // 'rate_limit' => $rateLimit,
            // 'rate_limit_remaining' => $rateLimitRemaining,
            'duration' => $responseTimeDelta,
        ]);



        return $pendingResponse;
    }

    private function getEndpoint(ResponseInterface $response): AmigoEndpoint
    {
        $connector = $this->getConnector($request);

        return $connector->endpoints()->firstOrCreate([
            'method' => $request->getMethod(),
            'path' => $request->getUri()->getPath(),
        ]);
    }

    private function getConnector(ResponseInterface $response): AmigoConnector
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

    private function getPath(ResponseInterface $response): string
    {
        $request = $response->
        $uri = $request->getUri();
        $path = $uri->getPath();
        $query = $uri->getQuery();
        if ($query !== '') {
            $path .= '?' . $query;
        }

        return $path;
    }
}
