<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('request_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_request_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('assignment_id')->nullable();
            $table->foreignId('uploaded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('category', 40);
            $table->string('original_name');
            $table->string('storage_path');
            $table->string('mime_type', 120);
            $table->unsignedBigInteger('size');
            $table->string('visibility', 30);
            $table->string('checksum', 64)->nullable();
            $table->timestamps();

            $table->index('assignment_id');
            $table->index(['service_request_id', 'visibility']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_attachments');
    }
};
