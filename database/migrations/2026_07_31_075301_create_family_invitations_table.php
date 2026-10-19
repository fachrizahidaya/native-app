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
        Schema::create('family_invitations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('family_id')
            ->costrained()
            ->cascadeOnDelete();

            $table->foreignId('invited_by')
            ->constrained('users')
            ->cascadeOnDelete();

            $table->string('email');
            $table->string('token');
            $table->enum('status', [ 
                'pending',
                'accepted',
                'cancelled',
                'expired'
            ])->default('pending');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('family_invitations');
    }
};
