<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guests', function (Blueprint $table) {
            $table->decimal('paid_amount', 12, 2)->default(0)->after('amount_contributed');
            $table->timestamp('last_payment_at')->nullable()->after('paid_amount');
        });

        Schema::create('guest_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('guest_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('method', 32)->default('cash');
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->date('paid_at');
            $table->timestamps();

            $table->index(['event_id', 'guest_id']);
            $table->index(['event_id', 'paid_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_payments');

        Schema::table('guests', function (Blueprint $table) {
            $table->dropColumn(['paid_amount', 'last_payment_at']);
        });
    }
};
