<?php

namespace App\Http\Controllers;

use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Single WhatsApp Cloud API webhook endpoint.
 *
 * GET  -> subscription verification handshake (hub.mode / hub.verify_token / hub.challenge)
 * POST -> event delivery (messages, statuses, ...) already signature-checked by middleware
 */
class WhatsAppWebhookController extends Controller
{
    public function __construct(private readonly WhatsAppService $whatsapp)
    {
    }

    public function __invoke(Request $request): Response
    {
        return $request->isMethod('GET')
            ? $this->verify($request)
            : $this->handle($request);
    }

    /**
     * Meta calls this once when you save the callback URL in the App Dashboard.
     * We must echo back hub.challenge verbatim when the token matches.
     */
    private function verify(Request $request): Response
    {
        $mode = $this->hubParam($request, 'mode');
        $token = $this->hubParam($request, 'verify_token');
        $challenge = $this->hubParam($request, 'challenge');

        $verifyToken = config('services.whatsapp.webhook_verify_token');

        if ($mode === 'subscribe' && ! empty($verifyToken) && is_string($token) && hash_equals($verifyToken, $token)) {
            return response($challenge ?? '', 200)
                ->header('Content-Type', 'text/plain');
        }

        Log::warning('WhatsApp webhook verification failed', ['mode' => $mode]);

        return response('Forbidden', 403)->header('Content-Type', 'text/plain');
    }

    /**
     * Meta retries any response that is not a 2xx, so we always acknowledge
     * with 200 and swallow/log processing errors instead of bubbling them up.
     */
    private function handle(Request $request): Response
    {
        $payload = $request->json()->all();

        Log::channel('whatsapp')->info('WhatsApp webhook received', ['payload' => $payload]);

        if (($payload['object'] ?? null) !== 'whatsapp_business_account') {
            return response()->json(['status' => 'ignored'], 200);
        }

        try {
            $this->whatsapp->processWebhook($payload);
        } catch (\Throwable $e) {
            Log::channel('whatsapp')->error('WhatsApp webhook processing failed: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
        }

        return response()->json(['status' => 'received'], 200);
    }

    /**
     * Meta sends "hub.mode" style keys; PHP rewrites the dot to an underscore
     * in $_GET, but some proxies/tests keep the dotted form, so accept both.
     */
    private function hubParam(Request $request, string $name): ?string
    {
        $value = $request->query('hub_' . $name, $request->query('hub.' . $name));

        return is_string($value) ? $value : null;
    }
}
