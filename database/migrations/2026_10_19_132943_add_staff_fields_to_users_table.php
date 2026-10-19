<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('mobile', 11)->nullable()->unique();
            $table->foreignId('organizational_unit_id')->nullable()->constrained('organizational_units')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();

            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('organizational_unit_id');
            $table->dropUnique(['mobile']);
            $table->dropColumn(['mobile', 'is_active', 'deleted_at']);
        });
    }
};
