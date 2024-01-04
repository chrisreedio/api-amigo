<?php

namespace ChrisReedIO\APIAmigo\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

use function config;

class WebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        if (! $this->validateSignature($request)) {
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $payload = json_decode($request->getContent(), true);

        // TODO: Enqueue the handling of the job

        return response()->json(['success' => true]);
    }

    private function validateSignature(Request $request): bool
    {
        // TODO - This should be configurable
        $signatureHeaderKey = config('webhooks.signature_header');
        $secret = config('webhooks.secret');

        $signature = $request->header($signatureHeaderKey);
        $payload = $request->getContent();

        $hash = hash_hmac('sha256', $payload, $secret);

        return $hash === $signature;
    }
}
