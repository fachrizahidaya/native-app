<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->after('name');
            $table->string('otp', 6)->nullable()->after('password');
            $table->timestamp('otp_expires_at')->nullable()->after('otp');
            $table->boolean('is_verified')->default(false)->after('otp_expires_at');
        });

        // Generate unique usernames for existing users
        $users = \App\Models\User::whereNull('username')->get();
        foreach ($users as $user) {
            $baseUsername = strtolower(str_replace(' ', '_', $user->name));
            $username = $baseUsername;
            $counter = 1;
            
            while (\App\Models\User::where('username', $username)->exists()) {
                $username = $baseUsername . '_' . $counter;
                $counter++;
            }
            
            $user->update(['username' => $username]);
        }

        // Now make username unique and not nullable
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable(false)->unique()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['username', 'otp', 'otp_expires_at', 'is_verified']);
        });
    }
};
