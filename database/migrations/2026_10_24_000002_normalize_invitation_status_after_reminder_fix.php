<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('invitations')
            ->whereIn('send_status', ['sent', 'failed', 'delivered'])
            ->whereExists(function ($query) {
                $query->selectRaw('1')
                    ->from('whatsapp_messages')
                    ->whereColumn('whatsapp_messages.invitation_id', 'invitations.id')
                    ->where('whatsapp_messages.message_type', 'reminder');
            })
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('whatsapp_messages')
                    ->whereColumn('whatsapp_messages.invitation_id', 'invitations.id')
                    ->where('whatsapp_messages.message_type', 'invitation');
            })
            ->update([
                'send_status' => 'pending',
                'sent_at' => null,
                'whatsapp_message_id' => null,
            ]);
    }

    public function down(): void
    {
        //
    }
};
