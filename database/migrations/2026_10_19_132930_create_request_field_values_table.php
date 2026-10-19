<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('request_field_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('request_field_id')->nullable()->constrained()->nullOnDelete();
            $table->string('field_key', 64);
            $table->string('label_snapshot');
            $table->string('field_type', 20);
            $table->text('value_text')->nullable();
            $table->decimal('value_number', 14, 2)->nullable();
            $table->date('value_date')->nullable();
            $table->json('value_json')->nullable();
            $table->timestamps();

            $table->index(['service_request_id', 'field_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_field_values');
    }
};
