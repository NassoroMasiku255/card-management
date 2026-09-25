<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->onDelete('cascade');
            $table->string('unique_id')->unique();
            $table->string('full_name');
            $table->string('phone_number');
            $table->decimal('amount_contributed', 12, 2)->default(0);
            $table->enum('card_type', ['single', 'double'])->default('single');
            $table->string('email')->nullable();
            $table->string('table_number')->nullable();
            $table->string('category')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['event_id', 'unique_id']);
            $table->index(['event_id', 'phone_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guests');
    }
};
