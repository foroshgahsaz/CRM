<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('message_templates', function (Blueprint $table) {
            $table->unsignedInteger('sms_ir_template_id')->nullable()->after('is_active');
            $table->json('sms_ir_parameter_map')->nullable()->after('sms_ir_template_id');
        });
    }

    public function down(): void
    {
        Schema::table('message_templates', function (Blueprint $table) {
            $table->dropColumn(['sms_ir_template_id', 'sms_ir_parameter_map']);
        });
    }
};
