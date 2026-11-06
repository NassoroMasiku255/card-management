<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('guest_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('invitation_id')->nullable()->constrained()->nullOnDelete();
            $table->string('message_type', 32);
            $table->string('to_phone');
            $table->string('from_name')->nullable();
            $table->text('text');
            $table->string('reference')->unique();
            $table->string('provider_message_id')->nullable();
            $table->string('status', 24)->default('queued');
            $table->json('response')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('status_updated_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['event_id', 'guest_id']);
            $table->index(['event_id', 'status']);
            $table->index(['event_id', 'message_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_messages');
    }
};