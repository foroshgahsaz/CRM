<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('otp_challenges', function (Blueprint $table) {
            $table->id();
            $table->string('mobile', 11);
            $table->string('purpose', 20);
            $table->string('tracking_code', 32)->nullable();
            $table->string('code_hash', 64);
            $table->timestamp('expires_at');
            $table->timestamp('verified_at')->nullable();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->unsignedSmallInteger('max_attempts');
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamp('resend_available_at')->nullable();
            $table->unsignedSmallInteger('send_count')->default(1);
            $table->string('provider_reference')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();

            $table->index(['mobile', 'purpose']);
            $table->index('tracking_code');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otp_challenges');
    }
};
