<?php

namespace ChrisReedIO\APIAmigo\Middleware\Saloon\Response;

use Saloon\Contracts\ResponseMiddleware;
use Saloon\Http\Response;

use function class_basename;
use function config;
use function dump;

class LogResponse implements ResponseMiddleware
{
    public function __invoke(Response $response): void
    {
        dump('== API Amigo - LogResponse middleware invoked ==');
        $responseIdKey = config('api-amigo.responses.headers.keys.request_id');
        $rateLimitKey = config('api-amigo.responses.headers.keys.rate.limit');
        $rateLimitRemainingKey = config('api-amigo.responses.headers.keys.rate.remaining');
        // Here we need to log the response
        // Important things to track are the request URL, the response status code, and any quota headers
        $pendingRequest = $response->getPendingRequest();
        $connector = $pendingRequest->getConnector();
        dump('Integration Connector: ' . class_basename($connector));

        $amigoRequestId = $pendingRequest->config()->get('amigo.request_id');
        $requestTime = $pendingRequest->config()->get('amigo.request_time');
        $responseTime = microtime(true);
        $responseTimeDelta = $responseTime - $requestTime;
        dump('Response Time: ' . round($responseTimeDelta, 3) . ' seconds');

        $rateLimit = $response->headers()->get($rateLimitKey);
        $rateLimitRemaining = $response->headers()->get($rateLimitRemainingKey);

        dump("Rate Limit Total: $rateLimit - Remaining: $rateLimitRemaining");

        // dump('Original Request Config:', $pendingRequest->config()->all());

        $responseRequestId = $response->headers()->get($responseIdKey);

        dump('Amigo Request ID: ' . $amigoRequestId);
        dump('Response Request ID: ' . $responseRequestId);

        // dump('Full Response Headers:');
        // dump($response->headers()->all());

        dd('');
    }
}
