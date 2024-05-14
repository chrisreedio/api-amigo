<?php

namespace ChrisReedIO\APIAmigo;

use ChrisReedIO\APIAmigo\Jobs\ProcessWebhookJob;
use ChrisReedIO\APIAmigo\Middleware\Guzzle\TrackGuzzleResponse;
use ChrisReedIO\APIAmigo\Middleware\Guzzle\TrackGuzzleRequest;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use ReflectionException;
use function array_merge;

class APIAmigo
{
    private static array $webhookHandlers = [];

    public static function registerWebhookHandler(string $handlerJobClass): void
    {
        // Ensure that the handler job class exists
        if (! class_exists($handlerJobClass)) {
            throw new \InvalidArgumentException("The webhook handler job class {$handlerJobClass} does not exist.");
        }

        // Also make sure it extends the ProcessWebhookJob class
        if (! is_subclass_of($handlerJobClass, ProcessWebhookJob::class)) {
            throw new \InvalidArgumentException("The webhook handler job class {$handlerJobClass} must extend the ProcessWebhookJob class.");
        }

        // static::$webhookHandlers[$event] = $handler;
        self::$webhookHandlers[] = $handlerJobClass;
    }

    public static function getWebhookHandlers(): array
    {
        return self::$webhookHandlers;
    }

    public static function hookGuzzle(Client $client): Client
    {
        // Get the existing client config via reflection
        try {
            $config = (new \ReflectionClass($client))->getProperty('config')->getValue($client);
        } catch (ReflectionException $e) {
            throw new \RuntimeException('Unable to access the Guzzle client configuration.');
        }

        // Create a handler stack from the existing client handler stack
        $handlerStack = HandlerStack::create($config['handler']);

        // Add the tracking middleware to the handler stack
        // $handlerStack->push(Middleware::mapRequest(new TrackGuzzleRequest), 'amigo-track_guzzle_request');
        // $handlerStack->push(Middleware::mapResponse(new TrackGuzzleResponse), 'amigo-log_guzzle_response');
        $handlerStack->push(new TrackGuzzleRequest, 'amigo-track_guzzle_request');
        $handlerStack->push(new TrackGuzzleResponse, 'amigo-log_guzzle_response');

        // Create a new Guzzle client with the modified handler stack
        return new Client(array_merge($config, ['handler' => $handlerStack]));
    }
}
