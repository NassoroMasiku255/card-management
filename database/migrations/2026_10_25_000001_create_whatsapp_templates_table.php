<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_templates', function (Blueprint $table) {
            $table->id();
            $table->string('template_id')->nullable();
            $table->string('name');
            $table->string('language', 16);
            $table->string('category', 64)->nullable();
            $table->string('status', 32)->default('APPROVED');
            $table->string('header_type', 32)->nullable();
            $table->text('body_text')->nullable();
            $table->json('components')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->unique(['name', 'language']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_templates');
    }
};
