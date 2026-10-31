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
        Schema::table('visitor_trackings', function (Blueprint $table) {
            $table->index('last_visited_at', 'visitor_trackings_last_visited_at_index');
            $table->index(['visit_date', 'visit_count'], 'visitor_trackings_visit_date_visit_count_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('visitor_trackings', function (Blueprint $table) {
            $table->dropIndex('visitor_trackings_last_visited_at_index');
            $table->dropIndex('visitor_trackings_visit_date_visit_count_index');
        });
    }
};
