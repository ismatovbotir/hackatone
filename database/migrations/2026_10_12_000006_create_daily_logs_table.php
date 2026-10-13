<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A day in the calendar: the client's own plan for it (optional, can be set ahead) and what they logged.
        // Missed training days are derived, not stored.
        Schema::create('daily_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->date('log_date');
            $table->string('level', 10);
            $table->string('session', 10); // planned session: cardio, strength, stretch, sport, recover, rest
            $table->unsignedSmallInteger('planned_minutes');
            $table->boolean('workout_done')->default(false);
            $table->unsignedSmallInteger('workout_minutes')->nullable();
            $table->json('meals')->nullable(); // one boolean per meal of the day's menu
            $table->unsignedSmallInteger('water_ml')->default(0);
            $table->unsignedSmallInteger('pages_read')->default(0); // mental growth: pages read that day
            $table->boolean('custom_plan')->default(false); // session and planned_minutes were chosen by the client
            $table->unsignedSmallInteger('reading_goal')->nullable(); // pages planned for the day
            $table->unsignedTinyInteger('points')->default(0); // App\Services\Clients\Points, stored for the leaderboard
            $table->timestamps();

            $table->unique(['client_id', 'log_date']);
            $table->index('log_date'); // leaderboard totals for a month across all clients
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_logs');
    }
};
