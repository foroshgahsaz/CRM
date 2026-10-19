<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_requests', function (Blueprint $table) {
            $table->id();
            $table->string('tracking_code', 32)->unique();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('request_type_id')->constrained()->restrictOnDelete();
            $table->date('purchase_date');
            $table->string('invoice_number')->nullable();
            $table->string('product_name')->nullable();
            $table->unsignedInteger('purchased_quantity')->nullable();
            $table->unsignedInteger('affected_quantity')->nullable();
            $table->text('description');
            $table->string('status', 40);
            $table->string('priority', 20)->default('normal');
            $table->unsignedBigInteger('current_assignment_id')->nullable();
            $table->foreignId('current_owner_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('current_unit_id')->nullable()->constrained('organizational_units')->nullOnDelete();
            $table->timestamp('submitted_at');
            $table->foreignId('approved_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->string('submission_key', 80)->nullable()->unique();
            $table->text('customer_visible_summary')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('priority');
            $table->index('submitted_at');
            $table->index('due_at');
            $table->index('current_assignment_id');
            $table->index(['status', 'due_at']);
            $table->index(['current_owner_id', 'status']);
            $table->index(['current_unit_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_requests');
    }
};
