<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 50)->unique();
            $table->string('location')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['is_active', 'name']);
        });

        Schema::create('warehouse_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('code', 80);
            $table->unsignedInteger('quantity')->default(0);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->unique(['warehouse_id', 'code']);
            $table->index('name');
            $table->index('code');
        });

        Schema::create('inventory_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_product_id')->constrained()->restrictOnDelete();
            $table->foreignId('service_request_id')->constrained()->restrictOnDelete();
            $table->string('product_name_snapshot');
            $table->string('product_code_snapshot', 80);
            $table->string('warehouse_name_snapshot');
            $table->foreignId('allocated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('cancel_reason')->nullable();
            $table->timestamps();
            $table->index(['service_request_id', 'cancelled_at']);
            $table->index(['warehouse_product_id', 'cancelled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_allocations');
        Schema::dropIfExists('warehouse_products');
        Schema::dropIfExists('warehouses');
    }
};
