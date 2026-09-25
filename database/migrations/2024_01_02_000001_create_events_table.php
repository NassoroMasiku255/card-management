<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('slug')->unique();
            $table->date('event_date');
            $table->time('event_time')->nullable();
            $table->string('location');
            $table->string('hall')->nullable();
            $table->text('description')->nullable();
            $table->string('card_template')->default('elegant');
            $table->string('card_background_color')->default('#ffffff');
            $table->string('card_text_color')->default('#333333');
            $table->string('card_accent_color')->default('#d4af37');
            $table->string('cover_image')->nullable();
            $table->enum('status', ['draft', 'active', 'completed', 'cancelled'])->default('draft');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
