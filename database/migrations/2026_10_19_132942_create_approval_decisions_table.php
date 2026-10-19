<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_request_id')->constrained()->cascadeOnDelete();
            $table->string('decision', 20);
            $table->foreignId('decided_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->text('reason')->nullable();
            $table->unsignedBigInteger('assignment_id')->nullable();
            $table->timestamps();

            $table->index(['service_request_id', 'decision']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_decisions');
    }
};
