<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Guest;
use App\Models\Invitation;
use App\Models\SmsMessage;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class NextSmsService
{
    public function send(Guest $guest, Event $event, string $messageType, ?Invitation $invitation = null): SmsMessage
    {
        $reference = (string) Str::uuid();
        $message = SmsMessage::create([
            'event_id' => $event->id,
            'guest_id' => $guest->id,
            'invitation_id' => $invitation?->id,
            'message_type' => $messageType,
            'to_phone' => $guest->formatted_phone,
            'from_name' => (string) config('services.nextsms.from'),
            'text' => $this->messageText($guest, $event, $messageType, $invitation),
            'reference' => $reference,
            'status' => 'queued',
        ]);

        $token = (string) config('services.nextsms.token');
        $from = (string) config('services.nextsms.from');

        if ($token === '' || $from === '' || $message->to_phone === '') {
            return $this->markFailed($message, 'NextSMS credentials or guest phone number are missing.');
        }

        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->asJson()
                ->timeout(30)
                ->post(config('services.nextsms.api_url'), [
                    'from' => $from,
                    'to' => $message->to_phone,
                    'text' => $message->text,
                    'flash' => 0,
                    'reference' => $reference,
                ]);

            $body = $response->json();

            if (! $response->successful()) {
                $error = data_get($body, 'message')
                    ?? data_get($body, 'error')
                    ?? $response->body()
                    ?? 'NextSMS rejected the message.';

                $message->update([
                    'status' => 'failed',
                    'response' => is_array($body) ? $body : ['body' => $response->body()],
                    'error_message' => (string) $error,
                    'status_updated_at' => now(),
                ]);

                return $message->refresh();
            }

            $message->update([
                'provider_message_id' => data_get($body, 'message_id')
                    ?? data_get($body, 'data.message_id')
                    ?? data_get($body, 'id'),
                'status' => 'sent',
                'response' => is_array($body) ? $body : ['body' => $response->body()],
                'status_updated_at' => now(),
                'sent_at' => now(),
            ]);
        } catch (ConnectionException $exception) {
            return $this->markFailed($message, $exception->getMessage());
        }

        return $message->refresh();
    }

    private function messageText(Guest $guest, Event $event, string $messageType, ?Invitation $invitation): string
    {
        if ($messageType === 'reminder') {
            $date = $event->event_date?->format('d/m/Y') ?? '';
            $amount = number_format((float) $guest->amount_contributed, 0);
            $paymentDetails = $event->paymentMethods()
                ->orderBy('id')
                ->get()
                ->map(fn ($method) => "{$method->payment_name}: {$method->payment_number}")
                ->implode('; ');

            if ($paymentDetails === '') {
                $paymentNumber = (string) config('services.nextsms.reminder_payment_number');
                $paymentName = (string) config('services.nextsms.reminder_payment_name');
                $paymentDetails = trim($paymentName.': '.$paymentNumber, ': ');
            }

            $paymentSentence = $paymentDetails !== '' ? " Namba za malipo: {$paymentDetails}." : '';

            return "Habari {$guest->full_name}, tunakushukuru kwa ahadi yako ya TZS {$amount} kwa ajili ya {$event->name}. Tunakukumbusha kwa upole kutuma mchango wako kabla ya tarehe {$date}.{$paymentSentence} Mungu akubariki sana. 🙏";
        }

        $date = $event->event_date?->format('d/m/Y') ?? '';
        $time = $event->event_time ?: '';
        $location = collect([$event->hall, $event->location, 'Dar es Salaam'])
            ->filter()
            ->unique(fn ($value) => mb_strtolower(trim((string) $value)))
            ->implode(', ');
        $link = $invitation ? route('invitation.public', $invitation->qr_code_data) : '';

        return "Ndugu/Rafiki {$guest->full_name}, Salamu nyingi kutoka kwetu! 🤍 Kwa neema ya Mungu, tunakualika katika {$event->name} tarehe {$date}, {$location}, saa {$time}. Tunakualika kwa upendo kuungana nasi katika siku hii maalum. Uwepo wako utaongeza furaha yetu. Namba ya mualika: {$guest->unique_id}. Ona muliko wako: {$link}";
    }

    private function markFailed(SmsMessage $message, string $error): SmsMessage
    {
        $message->update([
            'status' => 'failed',
            'error_message' => $error,
            'status_updated_at' => now(),
        ]);

        return $message->refresh();
    }
}