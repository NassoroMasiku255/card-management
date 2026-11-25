<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_user', function (Blueprint $table) {
            $table->boolean('can_send_whatsapp')->default(false)->after('user_id');
            $table->boolean('can_send_sms')->default(false)->after('can_send_whatsapp');
        });

        // Make sure every event owner has an access row, so an owner who could
        // already send does not lose it. Existing rows are left untouched.
        DB::table('events')
            ->select('id', 'user_id')
            ->whereNotNull('user_id')
            ->orderBy('id')
            ->chunkById(100, function ($events) {
                $now = now();

                DB::table('event_user')->insertOrIgnore($events->map(fn ($event) => [
                    'event_id' => $event->id,
                    'user_id' => $event->user_id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all());
            });

        // Users who could already send keep that on every event they can
        // access today, for both WhatsApp and SMS.
        $senderIds = DB::table('users')->where('can_send_whatsapp', true)->pluck('id');

        if ($senderIds->isNotEmpty()) {
            DB::table('event_user')->whereIn('user_id', $senderIds)->update([
                'can_send_whatsapp' => true,
                'can_send_sms' => true,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('event_user', function (Blueprint $table) {
            $table->dropColumn(['can_send_whatsapp', 'can_send_sms']);
        });
    }
};
