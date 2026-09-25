<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Validates the X-Hub-Signature-256 header that Meta attaches to every
 * webhook POST request.
 *
 * The signature is "sha256=" followed by the HMAC-SHA256 of the *raw*
 * request body, keyed with the Meta App Secret. GET requests (the
 * subscription handshake) are not signed, so they are passed through.
 */
class VerifyWhatsAppWebhookSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('GET')) {
            return $next($request);
        }

        $appSecret = config('services.whatsapp.app_secret');

        if (empty($appSecret)) {
            Log::warning('WhatsApp webhook signature not verified: WHATSAPP_APP_SECRET is not configured.');

            return $next($request);
        }

        $header = $request->header('X-Hub-Signature-256');

        if (! is_string($header) || ! str_starts_with($header, 'sha256=')) {
            Log::warning('WhatsApp webhook rejected: missing X-Hub-Signature-256 header.');

            return response()->json(['message' => 'Invalid signature'], 403);
        }

        $expected = hash_hmac('sha256', $request->getContent(), $appSecret);

        if (! hash_equals($expected, substr($header, strlen('sha256=')))) {
            Log::warning('WhatsApp webhook rejected: signature mismatch.');

            return response()->json(['message' => 'Invalid signature'], 403);
        }

        return $next($request);
    }
}
