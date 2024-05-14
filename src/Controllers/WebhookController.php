<?php

namespace ChrisReedIO\APIAmigo\Controllers;

use ChrisReedIO\APIAmigo\Jobs\ProcessWebhookJob;
use ChrisReedIO\APIAmigo\Models\AmigoListener;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

use function config;
use function json_validate;

class WebhookController extends Controller
{
    public function __invoke(Request $request, AmigoListener $listener): JsonResponse
    {
        if ($listener->webhook_secret !== null) {
            $secret = $listener->webhook_secret;
            if (! $this->validateSignature($request, $secret)) {
                return response()->json(['error' => 'Invalid signature'], 401);
            }
        } elseif ($listener->integration()->exists() && $listener->integration->webhook_secret !== null) {
            $secret = $listener->integration->webhook_secret;
            if (!$this->validateSignature($request, $secret)) {
                return response()->json(['error' => 'Invalid signature'], 401);
            }
        }

        $requestBody = $request->getContent();
        $payload = json_validate($requestBody) ? json_decode($requestBody, true) : $request->getPayload()->all();
        // $payload = json_decode($request->getContent(), true);
        // dd($payload);
        // dump($request->getPayload());
        // dd($request->getContent());

        // Start building our webhook object
        $webhook = $listener->webhooks()->create([
            'sender' => $request->ip(),
            'headers' => $request->headers->all(),
            // 'payload' => $request->getContent(),
            'payload' => $payload,
        ]);

        // If the listener does not have a handler, we can't do anything with it
        if ($listener->handler === null) {
            $webhook->fail(500, 'No handler configured');

            return response()->json(['error' => 'Invalid Configuration'], 500);
        }

        // Get the listener's handler
        /** @var ProcessWebhookJob $handler */
        $handler = $listener->handler;
        // Create a new instance of the handler and dispatch it
        // TODO - Queue this job
        $job = $handler::dispatchSync($webhook);

        return response()->json(['webhook_unique_id' => $webhook->unique_id]);
    }

    private function validateSignature(Request $request, ?string $secret = null): bool
    {
        // TODO - This should be configurable
        $signatureHeaderKey = config('api-amigo.webhooks.signature_header');
        if ($secret === null) {
            $secret = config('api-amigo.webhooks.secret');
        }

        $signature = $request->header($signatureHeaderKey);
        $payload = $request->getContent();

        // dump($signature);
        // dump($payload);
        // dump('secret', $secret);

        $hash = hash_hmac('sha256', $payload, $secret);

        // dd($hash);

        return $hash === $signature;
    }
}
