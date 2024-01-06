<?php

namespace ChrisReedIO\APIAmigo\Controllers;

use ChrisReedIO\APIAmigo\Models\AmigoListener;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

use function config;

class WebhookController extends Controller
{
    public function __invoke(Request $request, AmigoListener $listener): JsonResponse
    {
        if ($listener->webhook_secret !== null || $listener->integration->webhook_secret !== null) {
            $secret = $listener->webhook_secret ?? $listener->integration->webhook_secret;
            if (! $this->validateSignature($request, $secret)) {
                return response()->json(['error' => 'Invalid signature'], 401);
            }
        }

        $payload = json_decode($request->getContent(), true);

        // TODO: Enqueue the handling of the job

        return response()->json(['success' => true]);
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

        $hash = hash_hmac('sha256', $payload, $secret);

        return $hash === $signature;
    }
}
