<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Example animation per training activity, e.g. "cardio-youth". Files live on the "uploads" disk.
        // AI results are drafts until an admin publishes them; clients only see the published file.
        Schema::create('activity_videos', function (Blueprint $table) {
            $table->string('key', 32)->primary();
            $table->string('media_path')->nullable();   // published file
            $table->string('media_type', 4)->nullable(); // gif or mp4
            $table->string('draft_path')->nullable();   // AI result waiting for review
            $table->string('draft_type', 4)->nullable();
            $table->string('job_id', 100)->nullable();  // AI generation in progress
            $table->timestamp('job_started_at')->nullable();
            $table->string('job_error')->nullable();
            $table->text('prompt')->nullable(); // null = use the built-in prompt
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_videos');
    }
};
