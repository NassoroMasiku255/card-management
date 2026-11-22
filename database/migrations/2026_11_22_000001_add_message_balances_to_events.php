<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->unsignedInteger('whatsapp_balance')->default(0)->after('payment_thank_you_enabled');
            $table->unsignedInteger('sms_balance')->default(0)->after('whatsapp_balance');
        });

        Schema::create('event_message_credits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('channel', 20);
            $table->string('type', 20);
            $table->integer('amount');
            $table->unsignedInteger('balance_after');
            $table->string('message_type', 50)->nullable();
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index(['event_id', 'channel', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_message_credits');

        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['whatsapp_balance', 'sms_balance']);
        });
    }
};
