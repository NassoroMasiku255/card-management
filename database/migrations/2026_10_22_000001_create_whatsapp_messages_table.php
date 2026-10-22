<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('guest_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('invitation_id')->nullable()->constrained()->nullOnDelete();

            $table->string('message_type', 32)->default('invitation');
            $table->string('to_phone');
            $table->string('template_name')->nullable();
            $table->string('language_code', 16)->nullable();
            $table->string('language_policy', 24)->default('deterministic');

            $table->string('whatsapp_message_id')->nullable()->unique();
            $table->string('status', 24)->default('queued');

            $table->json('payload')->nullable();
            $table->json('response')->nullable();
            $table->json('status_payload')->nullable();
            $table->json('error')->nullable();

            $table->string('error_code')->nullable();
            $table->string('error_title')->nullable();
            $table->text('error_message')->nullable();

            $table->timestamp('status_updated_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->index(['event_id', 'guest_id']);
            $table->index(['event_id', 'status']);
            $table->index(['event_id', 'message_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_messages');
    }
};
