<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Guest;
use App\Models\GuestPayment;
use App\Models\Invitation;
use App\Models\WhatsAppMessage;
use App\Models\WhatsAppTemplate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    private string $apiUrl;

    private string $phoneNumberId;

    private string $businessAccountId;

    private string $accessToken;

    private string $templateName;

    private string $templateLanguage;

    private string $invitationButtonIndex;

    private string $defaultImageUrl;

    private string $reminderTemplateName;

    private string $reminderTemplateLanguage;

    private string $reminderLanguagePolicy;

    private string $reminderPaymentDetails;

    private string $reminderAmountPrefix;

    private string $paymentTemplateName;

    private string $paymentTemplateLanguage;

    public function __construct()
    {
        $this->apiUrl = config('services.whatsapp.api_url') ?? 'https://graph.facebook.com/v25.0';
        $this->phoneNumberId = (string) (config('services.whatsapp.phone_number_id') ?? '');
        $this->businessAccountId = (string) (config('services.whatsapp.business_account_id') ?? '1088196013963166');
        $this->accessToken = (string) (config('services.whatsapp.access_token') ?? '');
        $this->templateName = (string) (config('services.whatsapp.template_name') ?? 'wedding_invitation');
        $this->templateLanguage = (string) (config('services.whatsapp.template_language') ?? 'en_US');
        $this->invitationButtonIndex = (string) (config('services.whatsapp.invitation_button_index') ?? '2');
        $this->defaultImageUrl = (string) (config('services.whatsapp.default_image_url') ?? '');
        $this->reminderTemplateName = (string) (config('services.whatsapp.reminder_template_name') ?? 'reminder_sw');
        $this->reminderTemplateLanguage = (string) (config('services.whatsapp.reminder_template_language') ?? 'sw');
        $this->reminderLanguagePolicy = (string) (config('services.whatsapp.reminder_language_policy') ?? 'deterministic');
        $this->reminderPaymentDetails = (string) (config('services.whatsapp.reminder_payment_details') ?? '');
        $this->reminderAmountPrefix = (string) (config('services.whatsapp.reminder_amount_prefix') ?? 'Mchango');
        $this->paymentTemplateName = (string) (config('services.whatsapp.payment_template_name') ?? 'payment_notification');
        $this->paymentTemplateLanguage = (string) (config('services.whatsapp.payment_template_language') ?? 'en');
    }

    /**
     * Fetch and synchronize message templates from Meta WhatsApp Business API into local database.
     */
    public function syncTemplates(): array
    {
        $accountId = $this->businessAccountId ?: $this->phoneNumberId;
        if (empty($this->accessToken) || empty($accountId)) {
            throw new \RuntimeException('WhatsApp access token or Business Account ID is not configured.');
        }

        $url = rtrim($this->apiUrl, '/')."/{$accountId}/message_templates";
        $synced = 0;
        $nextUrl = $url.'?limit=100';

        while ($nextUrl) {
            $response = Http::withToken($this->accessToken)
                ->acceptJson()
                ->get($nextUrl);

            if (! $response->successful()) {
                $errorMsg = $response->json('error.message') ?? $response->body();
                Log::channel('whatsapp')->error('Failed to fetch WhatsApp templates', [
                    'status' => $response->status(),
                    'error' => $errorMsg,
                ]);
                throw new \RuntimeException("WhatsApp API error: {$errorMsg}");
            }

            $data = $response->json('data') ?? [];

            foreach ($data as $item) {
                if (empty($item['name']) || empty($item['language'])) {
                    continue;
                }

                $components = $item['components'] ?? [];
                $bodyText = null;
                $headerType = null;

                foreach ($components as $component) {
                    $type = strtoupper((string) ($component['type'] ?? ''));
                    if ($type === 'BODY') {
                        $bodyText = $component['text'] ?? null;
                    } elseif ($type === 'HEADER') {
                        $headerType = strtoupper((string) ($component['format'] ?? 'TEXT'));
                    }
                }

                WhatsAppTemplate::updateOrCreate(
                    [
                        'name' => $item['name'],
                        'language' => $item['language'],
                    ],
                    [
                        'template_id' => $item['id'] ?? null,
                        'category' => $item['category'] ?? null,
                        'status' => strtoupper((string) ($item['status'] ?? 'APPROVED')),
                        'components' => $components,
                        'body_text' => $bodyText,
                        'header_type' => $headerType,
                        'synced_at' => now(),
                    ]
                );

                $synced++;
            }

            $nextUrl = $response->json('paging.next');
        }

        return [
            'success' => true,
            'count' => $synced,
            'message' => "Successfully synced {$synced} template(s) from WhatsApp.",
        ];
    }

    /**
     * Send the invitation to a guest as a WhatsApp template message.
     *
     * The payload follows the selected template's actual body placeholders and
     * URL button position so different approved invitation templates can be used.
     */
    public function sendInvitation(Invitation $invitation, ?string $templateName = null, ?string $templateLanguage = null): bool
    {
        $guest = $invitation->guest;
        $event = $invitation->event;

        $template = $templateName ?: $this->templateName;
        $dbTemplate = $templateName
            ? $this->resolveInvitationTemplate($template, $templateLanguage)
            : $this->defaultInvitationTemplate();

        if ($dbTemplate) {
            $template = $dbTemplate->name;
        }

        $language = $templateLanguage
            ?: $dbTemplate?->language
            ?: $this->templateLanguage;

        $imageUrl = (! $dbTemplate || strtoupper((string) $dbTemplate->header_type) === 'IMAGE')
            ? ($invitation->card_image_url
                ?: ($event->cover_image
                    ? asset('storage/'.$event->cover_image)
                    : $this->defaultImageUrl))
            : null;

        $values = $this->invitationParameterValues($invitation);
        $bodyParameters = array_map(
            fn (string $key) => ['type' => 'text', 'text' => (string) $values[$key]],
            $this->invitationParameterKeys($dbTemplate?->body_text)
        );

        $buttonParameters = [];
        $buttonIndex = $this->templateUrlButtonIndex($dbTemplate);

        if ($buttonIndex !== null) {
            $buttonParameters = [
                'sub_type' => 'url',
                'index' => (string) $buttonIndex,
                'parameters' => [
                    ['type' => 'text', 'text' => (string) $invitation->qr_code_data],
                ],
            ];
        }

        return $this->sendTemplateMessage(
            phone: $guest->formatted_phone,
            templateName: $template,
            languageCode: $language,
            imageUrl: $imageUrl,
            bodyParameters: $bodyParameters,
            invitation: $invitation,
            messageType: 'invitation',
            buttonParameters: $buttonParameters,
        );
    }

    public function defaultInvitationTemplate(): ?WhatsAppTemplate
    {
        $configured = WhatsAppTemplate::where('name', $this->templateName)->first();

        if ($configured) {
            return $configured;
        }

        $templates = WhatsAppTemplate::approved()->get();

        return $templates->first(fn (WhatsAppTemplate $template) => $this->templateBodyParameterCount($template->body_text) === 5
                && $this->templateUrlButtonIndex($template) !== null)
            ?? $templates->first(fn (WhatsAppTemplate $template) => $this->templateUrlButtonIndex($template) !== null)
            ?? $templates->first();
    }

    private function resolveInvitationTemplate(string $template, ?string $language): ?WhatsAppTemplate
    {
        $query = WhatsAppTemplate::where('name', $template);

        if ($language) {
            $match = (clone $query)->where('language', $language)->first();

            if ($match) {
                return $match;
            }
        }

        return $query->first();
    }

    private function invitationParameterValues(Invitation $invitation): array
    {
        $guest = $invitation->guest;
        $event = $invitation->event;

        return [
            'name' => $guest->full_name,
            'event' => $event->name,
            'date' => $event->event_date->format('d-M-Y'),
            'venue' => $this->invitationVenue($event),
            'time' => (string) ($event->event_time ?? ''),
            'code' => (string) $invitation->qr_code_data,
            'link' => route('invitation.public', $invitation->qr_code_data),
        ];
    }

    private function invitationParameterKeys(?string $bodyText): array
    {
        return match ($this->templateBodyParameterCount($bodyText)) {
            1 => ['name'],
            2 => ['name', 'code'],
            3 => ['name', 'event', 'date'],
            4 => ['name', 'code', 'date', 'link'],
            0, 5 => ['name', 'event', 'date', 'venue', 'time'],
            default => array_slice(['name', 'event', 'date', 'venue', 'time', 'code', 'link'], 0, $this->templateBodyParameterCount($bodyText)),
        };
    }

    private function templateBodyParameterCount(?string $bodyText): int
    {
        if (! $bodyText) {
            return 0;
        }

        preg_match_all('/\{\{\s*(\d+)\s*\}\}/', $bodyText, $matches);

        return empty($matches[1]) ? 0 : max(array_map('intval', $matches[1]));
    }

    private function templateUrlButtonIndex(?WhatsAppTemplate $template): ?int
    {
        if (! $template) {
            return (int) $this->invitationButtonIndex;
        }

        $buttons = [];

        foreach ($template->components ?? [] as $component) {
            if (strtoupper((string) ($component['type'] ?? '')) === 'BUTTONS') {
                $buttons = $component['buttons'] ?? [];
                break;
            }
        }

        foreach ($buttons as $index => $button) {
            if (strtoupper((string) ($button['type'] ?? '')) === 'URL') {
                return (int) $index;
            }
        }

        return null;
    }

    private function invitationVenue(Event $event): string
    {
        return trim(implode(' ', array_filter([
            $event->hall,
            $event->location,
        ])));
    }

    /**
     * Send the configured reminder template. The reminder_sw template expects
     * four body parameters in this exact order:
     *   1. guest full name
     *   2. contribution amount (e.g. "Mchango 200,000 TZS")
     *   3. event name
     *   4. payment details (bank accounts / mobile money)
     */
    public function sendReminder(Guest $guest, ?Invitation $invitation = null, ?string $templateName = null, ?string $templateLanguage = null): bool
    {
        $event = $guest->event;

        $template = $templateName ?: $this->reminderTemplateName;
        $language = $templateLanguage;

        if (! $language && $template) {
            $dbTemplate = WhatsAppTemplate::where('name', $template)->first();
            if ($dbTemplate) {
                $language = $dbTemplate->language;
            }
        }

        $language = $language ?: $this->reminderTemplateLanguage;

        $bodyParameters = [
            ['type' => 'text', 'text' => $guest->full_name],
            ['type' => 'text', 'text' => $this->formatContribution($guest)],
            ['type' => 'text', 'text' => $event?->name ?? ''],
            ['type' => 'text', 'text' => $this->paymentDetails($event)],
        ];

        return $this->sendTemplateMessage(
            phone: $guest->formatted_phone,
            templateName: $template,
            languageCode: $language,
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
     * Send the payment thank-you template. The payment_notification template
     * expects four body parameters in this exact order:
     *   1. guest full name
     *   2. event name
     *   3. amount just paid (e.g. "TSh 40,000")
     *   4. final sentence (remaining balance, or a completion message)
     */
    public function sendPaymentThankYou(Guest $guest, GuestPayment $payment): bool
    {
        $event = $guest->event ?? $payment->event;

        if (empty($guest->formatted_phone)) {
            return false;
        }

        $template = $this->paymentTemplateName;
        $language = $this->paymentTemplateLanguage;

        $dbTemplate = WhatsAppTemplate::where('name', $template)->first();
        if ($dbTemplate) {
            $language = $dbTemplate->language;
        }

        $remaining = max(0, $guest->balance);
        $finalSentence = $remaining > 0
            ? '📌 *Kiasi kilichobaki:* TSh '.number_format($remaining, 0)
            : '✅ *Hali:* Umekamilisha mchango wako wote';

        $bodyParameters = [
            ['type' => 'text', 'text' => $guest->full_name],
            ['type' => 'text', 'text' => $event?->name ?? ''],
            ['type' => 'text', 'text' => 'TSh '.number_format((float) $payment->amount, 0)],
            ['type' => 'text', 'text' => $finalSentence],
        ];

        return $this->sendTemplateMessage(
            phone: $guest->formatted_phone,
            templateName: $template,
            languageCode: $language,
            imageUrl: null,
            bodyParameters: $bodyParameters,
            messageType: 'payment_thank_you',
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
        array $buttonParameters = [],
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

        if (! empty($buttonParameters)) {
            $components[] = [
                'type' => 'button',
                'sub_type' => $buttonParameters['sub_type'] ?? 'url',
                'index' => (string) ($buttonParameters['index'] ?? '0'),
                'parameters' => $this->sanitizeBodyParameters($buttonParameters['parameters'] ?? []),
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

                if ($invitation && $messageType === 'invitation') {
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

            if ($invitation && $messageType === 'invitation') {
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

            if ($invitation && $messageType === 'invitation') {
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

        if ($message && $message->message_type !== 'invitation') {
            return;
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
