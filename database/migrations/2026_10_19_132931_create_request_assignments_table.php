<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('request_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_assignment_id')->nullable()->constrained('request_assignments')->nullOnDelete();
            $table->foreignId('from_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('from_unit_id')->nullable()->constrained('organizational_units')->nullOnDelete();
            $table->foreignId('to_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('to_unit_id')->nullable()->constrained('organizational_units')->nullOnDelete();
            $table->string('assignment_type', 30);
            $table->string('status', 20);
            $table->unsignedBigInteger('active_lock')->nullable()->unique();
            $table->text('instructions')->nullable();
            $table->text('reason')->nullable();
            $table->timestamp('first_viewed_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('work_started_at')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('completion_summary')->nullable();
            $table->timestamps();

            $table->index(['service_request_id', 'status']);
            $table->index(['to_user_id', 'status']);
            $table->index(['to_unit_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_assignments');
    }
};
