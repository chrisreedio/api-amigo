<?php

namespace ChrisReedIO\APIAmigo;

use ChrisReedIO\APIAmigo\Jobs\ProcessWebhookJob;

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
}
