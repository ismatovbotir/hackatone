<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Health profile of a user with the "user" role. Email and password live on users.
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            // Digits only, with country code, e.g. 998901234567.
            $table->string('phone', 15)->unique();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->date('birth_date');
            $table->char('sex', 1); // M or F, picks the WHO curve
            $table->string('lang', 2)->default('uz');
            $table->string('level', 10)->nullable(); // easy, mid, strong; null = recommended
            $table->json('reminders');
            $table->time('reminder_time')->default('18:00');
            $table->bigInteger('telegram_chat_id')->nullable()->unique();
            $table->timestamp('telegram_linked_at')->nullable();
            $table->date('last_reminded_on')->nullable(); // daily reminder sent for this local date
            $table->timestamp('measure_reminded_at')->nullable();
            $table->dateTime('consent_at'); // always set at registration; dateTime so strict MySQL needs no default
            $table->timestamps();

            $table->index(['reminder_time', 'telegram_chat_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
