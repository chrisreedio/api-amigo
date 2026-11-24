<?php

namespace ChrisReedIO\APIAmigo\Middleware\Guzzle;

use ChrisReedIO\APIAmigo\Models\AmigoRequest;
use ChrisReedIO\APIAmigo\Models\AmigoResponse;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

use function trim;

class TrackGuzzleResponse
{
    public function __invoke(callable $handler)
    {
        return function (RequestInterface $request, array $options) use ($handler) {
            return $handler($request, $options)->then(
                function (ResponseInterface $response) use ($request, $options) {
                    // Set by TrackGuzzleRequest.
                    $amigo = $options['amigo'];

                    $captured = $amigo['capture_payload'];
                    $recordBody = $captured || AmigoRequest::capturesPayloadOnError($response->getStatusCode() >= 400);

                    // Keep the request's payload alongside a response body captured because it failed.
                    if ($recordBody && ! $captured) {
                        AmigoRequest::recordPayload($amigo['request_key'], AmigoRequest::psrPayload($request));
                    }

                    AmigoResponse::create([
                        'endpoint_id' => $amigo['endpoint_id'],
                        'request_id' => $amigo['request_key'],
                        'request_unique_id' => $amigo['request_id'],
                        'status_code' => $response->getStatusCode(),
                        'headers' => $response->getHeaders(),
                        'body' => $recordBody ? trim((string) $response->getBody()) : null,
                        'duration' => microtime(true) - $amigo['request_time'],
                    ]);

                    return $response;
                }
            );
        };
    }
}
