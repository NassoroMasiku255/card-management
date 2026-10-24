<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Guest;
use App\Models\Invitation;
use App\Models\WhatsAppMessage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    private string $apiUrl;

    private string $phoneNumberId;

    private string $accessToken;

    private string $templateName;

    private string $templateLanguage;

    private string $defaultImageUrl;

    private string $reminderTemplateName;

    private string $reminderTemplateLanguage;

    private string $reminderLanguagePolicy;

    private string $reminderPaymentDetails;

    private string $reminderAmountPrefix;

    public function __construct()
    {
        $this->apiUrl = config('services.whatsapp.api_url') ?? '';
        $this->phoneNumberId = config('services.whatsapp.phone_number_id') ?? '';
        $this->accessToken = config('services.whatsapp.access_token') ?? '';
        $this->templateName = config('services.whatsapp.template_name') ?? '';
        $this->templateLanguage = config('services.whatsapp.template_language') ?? 'en';
        $this->defaultImageUrl = config('services.whatsapp.default_image_url') ?? '';
        $this->reminderTemplateName = config('services.whatsapp.reminder_template_name') ?? 'reminder_sw';
        $this->reminderTemplateLanguage = config('services.whatsapp.reminder_template_language') ?? 'sw';
        $this->reminderLanguagePolicy = config('services.whatsapp.reminder_language_policy') ?? 'deterministic';
        $this->reminderPaymentDetails = config('services.whatsapp.reminder_payment_details') ?? '';
        $this->reminderAmountPrefix = config('services.whatsapp.reminder_amount_prefix') ?? 'Mchango';
    }

    /**
     * Send the invitation to a guest as a WhatsApp template message
     * (header image + body text parameters), e.g.:
     *
     * POST {apiUrl}/{phoneNumberId}/messages
     * {
     *   "messaging_product": "whatsapp",
     *   "to": "<guest phone>",
     *   "type": "template",
     *   "template": {
     *     "name": "<template name>",
     *     "language": { "code": "<language code>" },
     *     "components": [
     *       { "type": "header", "parameters": [{ "type": "image", "image": { "link": "<image url>" } }] },
     *       { "type": "body", "parameters": [ ... text parameters ... ] }
     *     ]
     *   }
     * }
     */
    public function sendInvitation(Invitation $invitation, ?string $templateName = null): bool
    {
        $guest = $invitation->guest;
        $event = $invitation->event;

        $imageUrl = $invitation->card_image_url
            ?: ($event->cover_image
                ? asset('storage/'.$event->cover_image)
                : $this->defaultImageUrl);

        $template = $templateName ?? $this->templateName;

        $bodyParameters = [
            ['type' => 'text', 'text' => $guest->full_name],
            ['type' => 'text', 'text' => $guest->unique_id],
            ['type' => 'text', 'text' => $event->event_date->format('M d, Y')],
        ];

        return $this->sendTemplateMessage(
            phone: $guest->formatted_phone,
            templateName: $template,
            languageCode: $this->templateLanguage,
            imageUrl: $imageUrl,
            bodyParameters: $bodyParameters,
            invitation: $invitation,
            messageType: 'invitation',
        );
    }

    /**
     * Send the configured reminder template. The reminder_sw template expects
     * four body parameters in this exact order:
     *   1. guest full name
     *   2. contribution amount (e.g. "Mchango 200,000 TZS")
     *   3. event name
     *   4. payment details (bank accounts / mobile money)
     */
    public function sendReminder(Guest $guest, ?Invitation $invitation = null, ?string $templateName = null): bool
    {
        $event = $guest->event;

        $bodyParameters = [
            ['type' => 'text', 'text' => $guest->full_name],
            ['type' => 'text', 'text' => $this->formatContribution($guest)],
            ['type' => 'text', 'text' => $event?->name ?? ''],
            ['type' => 'text', 'text' => $this->paymentDetails($event)],
        ];

        return $this->sendTemplateMessage(
            phone: $guest->formatted_phone,
            templateName: $templateName ?: $this->reminderTemplateName,
            languageCode: $this->reminderTemplateLanguage,
            imageUrl: null,
            bodyParameters: $bodyParameters,
            invitation: $invitation,
            messageType: 'reminder',
            languagePolicy: $this->reminderLanguagePolicy,
            guest: $guest,
            event: $event,
        );
    }

    /**
     * Generic sender: phone number, template name, language, header image
     * and body parameter values are all taken as request-time inputs
     * (backed by config for the Bearer token / API URL) rather than hardcoded.
     *
     * Every attempt is persisted in whatsapp_messages before the HTTP call so
     * failed requests are visible and webhook statuses can be matched by id.
     */
    public function sendTemplateMessage(
        string $phone,
        string $templateName,
        string $languageCode,
        ?string $imageUrl,
        array $bodyParameters = [],
        ?Invitation $invitation = null,
        string $messageType = 'invitation',
        string $languagePolicy = 'deterministic',
        ?Guest $guest = null,
        ?Event $event = null,
    ): bool {
        $components = [];

        if (! empty($imageUrl)) {
            $components[] = [
                'type' => 'header',
                'parameters' => [
                    [
                        'type' => 'image',
                        'image' => ['link' => $imageUrl],
                    ],
                ],
            ];
        }

        if (! empty($bodyParameters)) {
            $components[] = [
                'type' => 'body',
                'parameters' => $this->sanitizeBodyParameters($bodyParameters),
            ];
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $phone,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => [
                    'policy' => $languagePolicy,
                    'code' => $languageCode,
                ],
                'components' => $components,
            ],
        ];

        $message = WhatsAppMessage::create([
            'event_id' => $invitation?->event_id ?? $event?->id ?? $guest?->event_id,
            'guest_id' => $invitation?->guest_id ?? $guest?->id,
            'invitation_id' => $invitation?->id,
            'message_type' => $messageType,
            'to_phone' => $phone,
            'template_name' => $templateName,
            'language_code' => $languageCode,
            'language_policy' => $languagePolicy,
            'status' => WhatsAppMessage::STATUS_QUEUED,
            'payload' => $payload,
        ]);

        try {
            $response = Http::withToken($this->accessToken)
                ->post("{$this->apiUrl}/{$this->phoneNumberId}/messages", $payload);

            if ($response->successful()) {
                $messageId = $response->json('messages.0.id');

                $message->update([
                    'whatsapp_message_id' => $messageId,
                    'status' => WhatsAppMessage::STATUS_ACCEPTED,
                    'response' => $response->json(),
                    'status_updated_at' => now(),
                ]);

                if ($invitation) {
                    $invitation->markAsSent($messageId);
                }

                Log::channel('whatsapp')->info('WhatsApp template message accepted', [
                    'to' => $phone,
                    'template' => $templateName,
                    'message_id' => $messageId,
                    'whatsapp_message_id' => $message->id,
                    'invitation_id' => $invitation?->id,
                ]);

                return true;
            }

            $error = $response->json('error', []);

            $message->update([
                'status' => WhatsAppMessage::STATUS_FAILED,
                'response' => $response->json(),
                'error' => $error,
                'error_code' => isset($error['code']) ? (string) $error['code'] : null,
                'error_title' => $error['type'] ?? null,
                'error_message' => $error['message'] ?? null,
                'failed_at' => now(),
                'status_updated_at' => now(),
            ]);

            Log::channel('whatsapp')->error('WhatsApp API error', [
                'status' => $response->status(),
                'body' => $response->json(),
                'to' => $phone,
                'template' => $templateName,
                'whatsapp_message_id' => $message->id,
                'invitation_id' => $invitation?->id,
            ]);

            if ($invitation) {
                $invitation->update(['send_status' => 'failed']);
            }

            return false;

        } catch (\Throwable $e) {
            $message->update([
                'status' => WhatsAppMessage::STATUS_FAILED,
                'error' => ['message' => $e->getMessage()],
                'error_message' => $e->getMessage(),
                'failed_at' => now(),
                'status_updated_at' => now(),
            ]);

            Log::channel('whatsapp')->error('WhatsApp sending failed: '.$e->getMessage(), [
                'invitation_id' => $invitation?->id,
                'whatsapp_message_id' => $message->id,
                'to' => $phone,
            ]);

            if ($invitation) {
                $invitation->update(['send_status' => 'failed']);
            }

            return false;
        }
    }

    /**
     * Meta rejects template parameters containing newlines, tabs or more than
     * four consecutive spaces (error 132018), so normalise them defensively.
     */
    private function sanitizeBodyParameters(array $parameters): array
    {
        return array_map(function (array $parameter) {
            if (($parameter['type'] ?? null) === 'text' && isset($parameter['text'])) {
                $parameter['text'] = $this->sanitizeTemplateText((string) $parameter['text']);
            }

            return $parameter;
        }, $parameters);
    }

    private function sanitizeTemplateText(string $text): string
    {
        $text = str_replace(["\r\n", "\r", "\n", "\t"], ' ', $text);
        $text = preg_replace('/ {5,}/', ' ', $text) ?? $text;

        return trim($text);
    }

    private function formatContribution(Guest $guest): string
    {
        $amount = (float) $guest->amount_contributed;

        return trim($this->reminderAmountPrefix.' '.number_format($amount, 0).' TZS');
    }

    private function paymentDetails(?Event $event): string
    {
        if ($this->reminderPaymentDetails !== '') {
            return $this->reminderPaymentDetails;
        }

        return $event?->description ?? '';
    }

    /**
     * Walk the Cloud API webhook envelope:
     * entry[] -> changes[] -> value{ messages[], statuses[], errors[] }
     */
    public function processWebhook(array $payload): void
    {
        foreach ($payload['entry'] ?? [] as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                if (($change['field'] ?? 'messages') !== 'messages') {
                    continue;
                }

                $value = $change['value'] ?? [];

                foreach ($value['statuses'] ?? [] as $status) {
                    $this->processStatus($status);
                }

                foreach ($value['messages'] ?? [] as $message) {
                    $this->processMessage($message);
                }

                foreach ($value['errors'] ?? [] as $error) {
                    Log::channel('whatsapp')->error('WhatsApp account-level error', ['error' => $error]);
                }
            }
        }
    }

    /**
     * Delivery receipts: accepted / sent / delivered / read / failed.
     * The full status object is stored so conversation/pricing details are kept.
     */
    private function processStatus(array $status): void
    {
        $messageId = $status['id'] ?? null;
        $state = $status['status'] ?? null;

        if (! $messageId || ! $state) {
            return;
        }

        $message = WhatsAppMessage::where('whatsapp_message_id', $messageId)->first();

        if ($message && $this->shouldApplyStatus($message->status, $state)) {
            $message->applyStatus($state, $status);
        }

        $invitation = $message?->invitation
            ?? Invitation::where('whatsapp_message_id', $messageId)->first();

        if (! $invitation) {
            return;
        }

        $sendStatus = match ($state) {
            'sent' => 'sent',
            'delivered', 'read' => 'delivered',
            'failed' => 'failed',
            default => null,
        };

        if ($sendStatus && $invitation->send_status !== $sendStatus) {
            $invitation->update(['send_status' => $sendStatus]);
        }

        if ($state === 'failed') {
            Log::channel('whatsapp')->error('WhatsApp message failed', [
                'invitation_id' => $invitation->id,
                'whatsapp_message_id' => $message?->id,
                'errors' => $status['errors'] ?? [],
            ]);
        }
    }

    /**
     * Statuses can arrive out of order. Only move forward, except when a
     * failure arrives it always wins.
     */
    private function shouldApplyStatus(?string $current, string $incoming): bool
    {
        if ($current === WhatsAppMessage::STATUS_FAILED) {
            return $incoming === WhatsAppMessage::STATUS_FAILED;
        }

        if ($incoming === WhatsAppMessage::STATUS_FAILED) {
            return true;
        }

        if (! array_key_exists($incoming, WhatsAppMessage::STATUS_RANK)) {
            return false;
        }

        return WhatsAppMessage::rank($incoming) >= WhatsAppMessage::rank($current);
    }

    private function processMessage(array $message): void
    {
        $type = $message['type'] ?? null;
        $from = $message['from'] ?? null;

        $reply = match ($type) {
            'interactive' => $message['interactive']['button_reply']['id']
                ?? $message['interactive']['list_reply']['id']
                ?? null,
            'button' => $message['button']['payload'] ?? $message['button']['text'] ?? null,
            'text' => $message['text']['body'] ?? null,
            default => null,
        };

        if (! is_string($reply) || $reply === '') {
            return;
        }

        $invitation = $this->resolveInvitation($reply, $from);

        if (! $invitation) {
            Log::channel('whatsapp')->info('WhatsApp reply could not be matched to an invitation', [
                'from' => $from,
                'type' => $type,
                'reply' => $reply,
            ]);

            return;
        }

        $rsvp = $this->resolveRsvpStatus($reply);

        if (! $rsvp) {
            return;
        }

        $invitation->markRsvp($rsvp);

        Log::channel('whatsapp')->info('RSVP received', [
            'invitation_id' => $invitation->id,
            'status' => $rsvp,
            'from' => $from,
        ]);
    }

    /**
     * Prefer the invitation id encoded in the button payload
     * (rsvp_yes_{id} / rsvp_no_{id}); otherwise fall back to the sender's
     * phone number, which is delivered in full international format.
     */
    private function resolveInvitation(string $reply, ?string $from): ?Invitation
    {
        if (preg_match('/^rsvp_(?:yes|no)_(\d+)$/i', $reply, $matches)) {
            return Invitation::find((int) $matches[1]);
        }

        if (! $from) {
            return null;
        }

        $phone = preg_replace('/\D/', '', $from);

        return Invitation::whereHas('guest', function ($query) use ($phone) {
            $query->whereRaw(
                "REPLACE(REPLACE(REPLACE(REPLACE(phone_number, ' ', ''), '-', ''), '(', ''), ')', '') LIKE ?",
                ['%'.substr($phone, -9)]
            );
        })->latest('id')->first();
    }

    private function resolveRsvpStatus(string $reply): ?string
    {
        $normalized = strtolower(trim($reply));

        if (preg_match('/^rsvp_(yes|no)(_\d+)?$/', $normalized, $matches)) {
            return $matches[1] === 'yes' ? 'attending' : 'not_attending';
        }

        return match (true) {
            in_array($normalized, ['yes', 'y', 'ndiyo', 'nitakuja', 'attending', 'confirm'], true) => 'attending',
            in_array($normalized, ['no', 'n', 'hapana', 'sitakuja', 'not attending', 'decline'], true) => 'not_attending',
            default => null,
        };
    }
}
