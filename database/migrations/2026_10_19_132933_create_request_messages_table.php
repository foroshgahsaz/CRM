<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('request_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_request_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('assignment_id')->nullable();
            $table->foreignId('sender_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('recipient_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('message_type', 40);
            $table->string('visibility', 30);
            $table->text('body');
            $table->unsignedBigInteger('corrects_message_id')->nullable();
            $table->boolean('is_final')->default(false);
            $table->timestamps();

            $table->index('assignment_id');
            $table->index(['service_request_id', 'visibility']);
            $table->foreign('corrects_message_id')->references('id')->on('request_messages')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_messages');
    }
};
