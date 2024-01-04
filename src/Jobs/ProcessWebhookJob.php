<?php

namespace ChrisReedIO\APIAmigo\Jobs;

use ChrisReedIO\APIAmigo\Models\AmigoWebhook;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

abstract class ProcessWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected AmigoWebhook $webhook)
    {
    }

    final public function handle(): void
    {
        try {
            // Process the webhook
            if ($this->process()) {
                // Mark the webhook as successfully processed
                $this->webhook->complete();
            } else {
                // Mark the webhook as failed
                $this->webhook->fail(500, 'An unknown error occurred.');
            }
        } catch (\Exception $exception) {
            // Log the exception and mark the webhook as failed
            $this->webhook->failWithException($exception);
        }
    }

    /**
     * Process the webhook
     *
     * @return bool Whether the webhook was processed successfully
     */
    abstract public function process(): bool;
}
