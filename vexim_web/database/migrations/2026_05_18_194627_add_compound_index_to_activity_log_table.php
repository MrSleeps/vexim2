<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('activity_log', function (Blueprint $table) {
            // Add the compound index for efficient timeline queries
            // This optimizes queries that filter by subject_type, subject_id, and sort by created_at
            $table->index(['subject_type', 'subject_id', 'created_at'], 'idx_subject_activity_timeline');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activity_log', function (Blueprint $table) {
            // Drop the index when rolling back
            $table->dropIndex('idx_subject_activity_timeline');
        });
    }
};
