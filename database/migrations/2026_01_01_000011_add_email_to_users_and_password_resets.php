<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email', 100)->nullable()->unique()->after('username');
        });

        // Berikan email default bagi akun terdaftar eksisting agar fitur email langsung berfungsi
        $users = DB::table('users')->get();
        foreach ($users as $user) {
            DB::table('users')->where('id', $user->id)->update([
                'email' => strtolower($user->username) . '@sekolah.sch.id'
            ]);
        }

        Schema::table('password_reset_tokens', function (Blueprint $table) {
            if (!Schema::hasColumn('password_reset_tokens', 'email')) {
                $table->string('email')->nullable()->after('username')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('password_reset_tokens', function (Blueprint $table) {
            if (Schema::hasColumn('password_reset_tokens', 'email')) {
                $table->dropIndex(['email']);
                $table->dropColumn('email');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('email');
        });
    }
};
