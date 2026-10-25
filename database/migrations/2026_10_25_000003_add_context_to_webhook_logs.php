<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('webhook_logs', function (Blueprint $table) {
            $table->foreignId('event_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->foreignId('guest_id')->nullable()->after('event_id')->constrained('guests')->nullOnDelete();
            $table->index(['event_id', 'event_type']);
        });
    }

    public function down(): void
    {
        Schema::table('webhook_logs', function (Blueprint $table) {
            $table->dropIndex(['event_id', 'event_type']);
            $table->dropConstrainedForeignId('guest_id');
            $table->dropConstrainedForeignId('event_id');
        });
    }
};
