<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_payment_methods', function (Blueprint $table) {
            $table->string('account_holder')->nullable()->after('payment_number');
        });
    }

    public function down(): void
    {
        Schema::table('event_payment_methods', function (Blueprint $table) {
            $table->dropColumn('account_holder');
        });
    }
};