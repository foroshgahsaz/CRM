<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_logs', function (Blueprint $table) {
            $table->id();
            $table->string('recipient', 20);
            $table->string('event_key', 80);
            $table->foreignId('service_request_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider', 40);
            $table->string('provider_reference')->nullable();
            $table->string('status', 20);
            $table->string('error_code', 50)->nullable();
            $table->string('error_message', 500)->nullable();
            $table->unsignedSmallInteger('attempt')->default(1);
            $table->string('idempotency_key', 120)->unique();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index('event_key');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_logs');
    }
};
