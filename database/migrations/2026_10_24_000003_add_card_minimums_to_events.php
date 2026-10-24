<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->decimal('single_card_min_amount', 12, 2)->nullable()->after('card_accent_color');
            $table->decimal('double_card_min_amount', 12, 2)->nullable()->after('single_card_min_amount');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['single_card_min_amount', 'double_card_min_amount']);
        });
    }
};
