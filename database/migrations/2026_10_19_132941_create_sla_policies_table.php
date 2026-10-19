<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sla_policies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('request_type_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('priority', 20)->nullable();
            $table->foreignId('organizational_unit_id')->nullable()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('response_minutes');
            $table->unsignedInteger('resolution_minutes');
            $table->boolean('use_business_hours')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'request_type_id', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sla_policies');
    }
};
