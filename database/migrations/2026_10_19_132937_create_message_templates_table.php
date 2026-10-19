<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_templates', function (Blueprint $table) {
            $table->id();
            $table->string('event_key', 80)->unique();
            $table->string('title');
            $table->text('body');
            $table->string('channel', 20)->default('sms');
            $table->boolean('is_active')->default(true);
            $table->json('allowed_variables');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_templates');
    }
};
