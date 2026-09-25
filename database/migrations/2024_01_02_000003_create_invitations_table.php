<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->onDelete('cascade');
            $table->foreignId('guest_id')->constrained()->onDelete('cascade');
            $table->string('qr_code_data');
            $table->string('qr_code_path')->nullable();
            $table->enum('send_status', ['pending', 'sent', 'failed', 'delivered'])->default('pending');
            $table->timestamp('sent_at')->nullable();
            $table->enum('rsvp_status', ['pending', 'attending', 'not_attending'])->default('pending');
            $table->timestamp('rsvp_responded_at')->nullable();
            $table->enum('attendance_status', ['pending', 'attended', 'not_attended'])->default('pending');
            $table->timestamp('checked_in_at')->nullable();
            $table->string('whatsapp_message_id')->nullable();
            $table->timestamps();

            $table->unique(['event_id', 'guest_id']);
            $table->index(['event_id', 'rsvp_status']);
            $table->index(['event_id', 'attendance_status']);
            $table->index('qr_code_data');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invitations');
    }
};
