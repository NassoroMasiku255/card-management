<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE invitations MODIFY send_status ENUM('pending', 'sent', 'failed', 'delivered', 'read') NOT NULL DEFAULT 'pending'");

            return;
        }

        if ($driver === 'sqlite') {
            Schema::table('invitations', function (Blueprint $table) {
                $table->enum('send_status', ['pending', 'sent', 'failed', 'delivered', 'read'])->default('pending')->change();
            });
        }
    }

    public function down(): void
    {
        DB::table('invitations')->where('send_status', 'read')->update(['send_status' => 'delivered']);

        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE invitations MODIFY send_status ENUM('pending', 'sent', 'failed', 'delivered') NOT NULL DEFAULT 'pending'");

            return;
        }

        if ($driver === 'sqlite') {
            Schema::table('invitations', function (Blueprint $table) {
                $table->enum('send_status', ['pending', 'sent', 'failed', 'delivered'])->default('pending')->change();
            });
        }
    }
};
