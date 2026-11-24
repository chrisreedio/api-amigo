<?php

namespace ChrisReedIO\APIAmigo\Tests\Fixtures\Webhooks;

use ChrisReedIO\APIAmigo\Jobs\ProcessWebhookJob;
use RuntimeException;

class ProcessTestWebhook extends ProcessWebhookJob
{
    public function process(): bool
    {
        if ($this->webhook->payload['fail'] ?? false) {
            throw new RuntimeException('Handler failed');
        }

        return ! ($this->webhook->payload['reject'] ?? false);
    }
}
