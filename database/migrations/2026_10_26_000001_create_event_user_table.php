<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['event_id', 'user_id']);
        });

        DB::table('events')
            ->select('id', 'user_id')
            ->orderBy('id')
            ->chunkById(100, function ($events) {
                $now = now();
                $rows = $events->map(fn ($event) => [
                    'event_id' => $event->id,
                    'user_id' => $event->user_id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all();

                DB::table('event_user')->insertOrIgnore($rows);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_user');
    }
};
