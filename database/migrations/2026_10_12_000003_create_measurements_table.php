<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('measurements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->date('measured_on');
            $table->decimal('height_cm', 4, 1);
            $table->decimal('weight_kg', 4, 1);
            $table->timestamps();

            $table->index(['client_id', 'measured_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('measurements');
    }
};
