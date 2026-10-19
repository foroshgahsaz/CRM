<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('request_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_request_id')->constrained()->cascadeOnDelete();
            $table->string('from_status', 40)->nullable();
            $table->string('to_status', 40);
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['service_request_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_status_histories');
    }
};
