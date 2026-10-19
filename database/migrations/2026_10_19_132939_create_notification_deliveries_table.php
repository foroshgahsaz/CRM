<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipient_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('notification_type');
            $table->string('channel', 20);
            $table->foreignId('service_request_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 20);
            $table->timestamp('delivered_at')->nullable();
            $table->string('failure_reason', 500)->nullable();
            $table->string('provider_reference')->nullable();
            $table->string('idempotency_key', 150)->nullable()->unique();
            $table->timestamps();

            $table->index(['recipient_id', 'channel']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_deliveries');
    }
};
