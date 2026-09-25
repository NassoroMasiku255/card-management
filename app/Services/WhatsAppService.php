<?php

namespace App\Services;

use App\Models\Invitation;
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

    public function __construct()
    {
        $this->apiUrl = config('services.whatsapp.api_url') ?? '';
        $this->phoneNumberId = config('services.whatsapp.phone_number_id') ?? '';
        $this->accessToken = config('services.whatsapp.access_token') ?? '';
        $this->templateName = config('services.whatsapp.template_name') ?? '';
        $this->templateLanguage = config('services.whatsapp.template_language') ?? 'en';
        $this->defaultImageUrl = config('services.whatsapp.default_image_url') ?? '';
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
    public function sendInvitation(Invitation $invitation): bool
    {
        $guest = $invitation->guest;
        $event = $invitation->event;

        $imageUrl = $event->cover_image
            ? asset('storage/' . $event->cover_image)
            : $this->defaultImageUrl;

        $bodyParameters = [
            ['type' => 'text', 'text' => $guest->full_name],
            ['type' => 'text', 'text' => $guest->unique_id],
            ['type' => 'text', 'text' => $event->event_date->format('M d, Y')],
        ];

        return $this->sendTemplateMessage(
            phone: $guest->formatted_phone,
            templateName: $this->templateName,
            languageCode: $this->templateLanguage,
            imageUrl: $imageUrl,
            bodyParameters: $bodyParameters,
            invitation: $invitation,
        );
    }

    /**
     * Generic sender: phone number, template name, language, header image
     * and body parameter values are all taken as request-time inputs
     * (backed by config for the Bearer token / API URL) rather than hardcoded.
     */
    public function sendTemplateMessage(
        string $phone,
        string $templateName,
        string $languageCode,
        ?string $imageUrl,
        array $bodyParameters = [],
        ?Invitation $invitation = null
    ): bool {
        $components = [];

        if (!empty($imageUrl)) {
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

        if (!empty($bodyParameters)) {
            $components[] = [
                'type' => 'body',
                'parameters' => $bodyParameters,
            ];
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $phone,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => [
                    'code' => $languageCode,
                ],
                'components' => $components,
            ],
        ];

        try {
            $response = Http::withToken($this->accessToken)
                ->post("{$this->apiUrl}/{$this->phoneNumberId}/messages", $payload);

            if ($response->successful()) {
                $messageId = $response->json('messages.0.id');

                if ($invitation) {
                    $invitation->markAsSent($messageId);
                }

                Log::info('WhatsApp template message sent', [
                    'to' => $phone,
                    'template' => $templateName,
                    'invitation_id' => $invitation?->id,
                ]);

                return true;
            }

            Log::error('WhatsApp API error', [
                'status' => $response->status(),
                'body' => $response->json(),
                'to' => $phone,
                'template' => $templateName,
            ]);

            if ($invitation) {
                $invitation->update(['send_status' => 'failed']);
            }

            return false;

        } catch (\Exception $e) {
            Log::error('WhatsApp sending failed: ' . $e->getMessage(), [
                'invitation_id' => $invitation?->id,
                'to' => $phone,
            ]);

            if ($invitation) {
                $invitation->update(['send_status' => 'failed']);
            }

            return false;
        }
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
     * Delivery receipts: sent / delivered / read / failed.
     * 'read' is folded into 'delivered' because send_status has no read state.
     */
    private function processStatus(array $status): void
    {
        $messageId = $status['id'] ?? null;
        $state = $status['status'] ?? null;

        if (!$messageId || !$state) {
            return;
        }

        $invitation = Invitation::where('whatsapp_message_id', $messageId)->first();

        if (!$invitation) {
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
                'errors' => $status['errors'] ?? [],
            ]);
        }
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

        if (!is_string($reply) || $reply === '') {
            return;
        }

        $invitation = $this->resolveInvitation($reply, $from);

        if (!$invitation) {
            Log::channel('whatsapp')->info('WhatsApp reply could not be matched to an invitation', [
                'from' => $from,
                'type' => $type,
                'reply' => $reply,
            ]);

            return;
        }

        $rsvp = $this->resolveRsvpStatus($reply);

        if (!$rsvp) {
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

        if (!$from) {
            return null;
        }

        $phone = preg_replace('/\D/', '', $from);

        return Invitation::whereHas('guest', function ($query) use ($phone) {
            $query->whereRaw(
                "REPLACE(REPLACE(REPLACE(REPLACE(phone_number, ' ', ''), '-', ''), '(', ''), ')', '') LIKE ?",
                ['%' . substr($phone, -9)]
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
