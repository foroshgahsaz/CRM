<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_access_sessions', function (Blueprint $table) {
            $table->foreign('service_request_id')->references('id')->on('service_requests')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('customer_access_sessions', function (Blueprint $table) {
            $table->dropForeign(['service_request_id']);
        });
    }
};
