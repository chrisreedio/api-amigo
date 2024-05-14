<?php

namespace ChrisReedIO\APIAmigo\Middleware\Guzzle;

use ChrisReedIO\APIAmigo\Models\AmigoConnector;
use ChrisReedIO\APIAmigo\Models\AmigoEndpoint;
use ChrisReedIO\APIAmigo\Models\AmigoIntegration;
use ChrisReedIO\APIAmigo\Models\AmigoRequest;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

use function config;
use function trim;

class TrackGuzzleResponse
{
    // public function __invoke(ResponseInterface $pendingResponse): ResponseInterface
    public function __invoke(callable $handler)
    {
        return function (RequestInterface $request, array $options) use ($handler) {
            return $handler($request, $options)->then(
                function (ResponseInterface $pendingResponse) use ($request, $options) {
                    // dump('== API Amigo LogGuzzleResponse middleware invoked ==');

                    $amigoRequestId = $options['amigo']['request_id'];
                    $responseTimeDelta = microtime(true) - $options['amigo']['request_time'];
                    $method = $options['amigo']['method'];
                    $uri = $request->getUri();

                    $connector = self::getConnector($uri->getHost());
                    // dd($request);
                    // dd($uri);
                    // dump($options);
                    // dd($pendingResponse);
                    // dd($pendingResponse->getHeaders());

                    // $endpoint = AmigoEndpoint::track($saloonResponse->getPendingRequest());
                    $endpoint = $connector->endpoints()->firstOrCreate([
                        'method' => $method,
                        'path' => $request->getUri()->getPath(),
                    ]);

                    // TODO: Update latest rate limit information
                    // $endpoint->connector->rate_limit = $rateLimit;
                    // $endpoint->connector->rate_limit_remaining = $rateLimitRemaining;
                    // $endpoint->connector->save();

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
                        $recordBody = config('api-amigo.responses.capture_body_on_error') && $pendingResponse->getStatusCode() >= 400;
                    }

                    // dd("'" . $saloonResponse->body() . "'");

                    $endpoint->responses()->create([
                        'request_id' => $request?->id,
                        'status_code' => $pendingResponse->getStatusCode(),
                        // 'status_message' => $response->status(),
                        'headers' => $pendingResponse->getHeaders(),
                        'body' => $recordBody ? trim($pendingResponse->getBody()->getContents()) : null,
                        'request_unique_id' => $amigoRequestId,
                        // 'rate_limit' => $rateLimit,
                        // 'rate_limit_remaining' => $rateLimitRemaining,
                        'duration' => $responseTimeDelta,
                    ]);

                    return $pendingResponse;
                    // return $handler($response, $options);
                }
            );
        };
    }

    private static function getConnector(string $host): AmigoConnector
    {
        $integration = AmigoIntegration::firstOrCreate([
            'name' => $host,
        ]);

        return AmigoConnector::firstOrCreate([
            'name' => $host . ' via Guzzle',
            'integration_id' => $integration->id,
        ], [
            'base_url' => $host,
        ]);
    }

    private static function getPath(RequestInterface $request): string
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
