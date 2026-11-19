<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->foreignId('internal_submitted_by_id')
                ->nullable()
                ->after('submission_key')
                ->constrained('users')
                ->nullOnDelete();
        });

        $logs = DB::table('audit_logs')
            ->select(['auditable_id', 'user_id'])
            ->whereIn('event', ['request.created_internal', 'request.submitted'])
            ->where('auditable_type', 'App\Models\ServiceRequest')
            ->whereNotNull('user_id')
            ->orderBy('id')
            ->get();

        foreach ($logs as $log) {
            DB::table('service_requests')
                ->where('id', $log->auditable_id)
                ->whereNull('internal_submitted_by_id')
                ->update(['internal_submitted_by_id' => $log->user_id]);
        }
    }

    public function down(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('internal_submitted_by_id');
        });
    }
};