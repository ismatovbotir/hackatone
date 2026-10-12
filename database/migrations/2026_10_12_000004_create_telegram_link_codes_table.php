<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_link_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            // "link": connect Telegram (shown on the site, sent to the bot). "login": sign in by phone (sent by the bot, typed on the site).
            $table->string('purpose', 8)->default('link');
            // HMAC of purpose + 6-digit code; the plain code is never stored.
            $table->string('code_hash', 64)->index();
            $table->dateTime('expires_at'); // always set; dateTime so strict MySQL needs no default
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_link_codes');
    }
};
