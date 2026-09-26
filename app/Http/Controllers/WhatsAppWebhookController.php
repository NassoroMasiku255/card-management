<?php

namespace App\Http\Controllers;

use App\Models\WebhookLog;
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

        $matched = $mode === 'subscribe'
            && ! empty($verifyToken)
            && is_string($token)
            && hash_equals($verifyToken, $token);

        WebhookLog::create([
            'direction' => 'incoming',
            'method' => 'GET',
            'event_type' => 'verification',
            'payload' => $request->query(),
            'summary' => $matched ? 'Verification succeeded' : 'Verification failed',
            'processed' => $matched,
            'status_code' => $matched ? '200' : '403',
        ]);

        if ($matched) {
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

        $relevantHeaders = $request->headers->all();

        if (($payload['object'] ?? null) !== 'whatsapp_business_account') {
            WebhookLog::create([
                'direction' => 'incoming',
                'method' => 'POST',
                'event_type' => 'ignored',
                'payload' => $payload,
                'headers' => $relevantHeaders,
                'summary' => 'Ignored: not a whatsapp_business_account object',
                'status_code' => '200',
                'processed' => false,
            ]);

            return response()->json(['status' => 'ignored'], 200);
        }

        $logs = $this->extractAndLog($payload, $relevantHeaders);

        try {
            $this->whatsapp->processWebhook($payload);

            WebhookLog::whereIn('id', $logs->pluck('id'))->update(['processed' => true]);
        } catch (\Throwable $e) {
            Log::channel('whatsapp')->error('WhatsApp webhook processing failed: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            WebhookLog::whereIn('id', $logs->pluck('id'))->update([
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json(['status' => 'received'], 200);
    }

    /**
     * Walk the Cloud API envelope and create a WebhookLog for each
     * meaningful event (status update, message, error).
     */
    private function extractAndLog(array $payload, array $headers): \Illuminate\Support\Collection
    {
        $logs = collect();

        foreach ($payload['entry'] ?? [] as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                $value = $change['value'] ?? [];

                foreach ($value['statuses'] ?? [] as $status) {
                    $logs->push(WebhookLog::create([
                        'direction' => 'incoming',
                        'method' => 'POST',
                        'event_type' => 'message_status',
                        'whatsapp_message_id' => $status['id'] ?? null,
                        'source_phone' => $status['recipient_id'] ?? null,
                        'summary' => 'Status: ' . ($status['status'] ?? 'unknown'),
                        'payload' => $payload,
                        'headers' => $headers,
                        'parsed_data' => $status,
                        'status_code' => '200',
                    ]));
                }

                foreach ($value['messages'] ?? [] as $message) {
                    $type = $message['type'] ?? 'unknown';
                    $eventType = match ($type) {
                        'interactive' => 'interactive_reply',
                        'button' => 'button_reply',
                        'text' => 'message_reply',
                        default => 'message_' . $type,
                    };

                    $logs->push(WebhookLog::create([
                        'direction' => 'incoming',
                        'method' => 'POST',
                        'event_type' => $eventType,
                        'whatsapp_message_id' => $message['id'] ?? null,
                        'source_phone' => $message['from'] ?? null,
                        'summary' => $this->summarizeMessage($message),
                        'payload' => $payload,
                        'headers' => $headers,
                        'parsed_data' => $message,
                        'status_code' => '200',
                    ]));
                }

                foreach ($value['errors'] ?? [] as $error) {
                    $logs->push(WebhookLog::create([
                        'direction' => 'incoming',
                        'method' => 'POST',
                        'event_type' => 'error',
                        'summary' => 'Error: ' . ($error['message'] ?? $error['title'] ?? 'unknown'),
                        'payload' => $payload,
                        'headers' => $headers,
                        'parsed_data' => $error,
                        'status_code' => '200',
                    ]));
                }
            }
        }

        if ($logs->isEmpty()) {
            $logs->push(WebhookLog::create([
                'direction' => 'incoming',
                'method' => 'POST',
                'event_type' => 'unknown',
                'payload' => $payload,
                'headers' => $headers,
                'summary' => 'Unrecognised payload structure',
                'status_code' => '200',
            ]));
        }

        return $logs;
    }

    private function summarizeMessage(array $message): string
    {
        $type = $message['type'] ?? 'unknown';

        return match ($type) {
            'text' => 'Text: ' . mb_substr($message['text']['body'] ?? '', 0, 80),
            'interactive' => 'Interactive: ' . ($message['interactive']['button_reply']['title']
                ?? $message['interactive']['list_reply']['title']
                ?? 'unknown'),
            'button' => 'Button: ' . ($message['button']['text'] ?? 'unknown'),
            'image' => 'Image message',
            'video' => 'Video message',
            'audio' => 'Audio message',
            'document' => 'Document message',
            'location' => 'Location message',
            'sticker' => 'Sticker message',
            'contacts' => 'Contacts message',
            default => 'Message type: ' . $type,
        };
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
