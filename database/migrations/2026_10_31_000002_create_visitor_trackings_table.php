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
        Schema::create('visitor_trackings', function (Blueprint $table) {
            $table->id();
            $table->string('visitor_hash', 64)->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->date('visit_date')->index();
            $table->unsignedInteger('visit_count')->default(1);
            $table->timestamp('first_visited_at');
            $table->timestamp('last_visited_at');
            $table->timestamps();

            $table->unique(['visitor_hash', 'visit_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visitor_trackings');
    }
};
