<?php

namespace Tests;

use App\Models\Event;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * New events start with no message balance, so tests that send
     * WhatsApp or SMS messages must fund the event first.
     */
    protected function fundEvent(Event $event, int $whatsapp = 100, int $sms = 100): Event
    {
        $event->forceFill(['whatsapp_balance' => $whatsapp, 'sms_balance' => $sms])->save();

        return $event;
    }
}
