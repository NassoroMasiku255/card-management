<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->string('direction', 10)->default('incoming');
            $table->string('method', 6);
            $table->string('event_type')->nullable();
            $table->string('status_code', 3)->nullable();
            $table->string('source_phone')->nullable();
            $table->string('whatsapp_message_id')->nullable();
            $table->string('summary')->nullable();
            $table->json('headers')->nullable();
            $table->json('payload')->nullable();
            $table->json('parsed_data')->nullable();
            $table->boolean('processed')->default(false);
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index('event_type');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_logs');
    }
};
