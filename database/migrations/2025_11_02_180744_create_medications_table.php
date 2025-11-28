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
        Schema::create('medications', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('human_summary')->nullable();
            $table->text('posology')->nullable();
            $table->text('indications')->nullable();
            $table->text('contraindications')->nullable();
            $table->text('interaction_alerts')->nullable();
            $table->json('composition')->nullable();
            $table->text('half_life_notes')->nullable();
            $table->text('storage_guidance')->nullable();
            $table->text('disclaimer')->nullable();
            $table->json('sources')->nullable();
            $table->string('source')->default('openai');
            $table->timestamp('fetched_at')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamps();

            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medications');
    }
};
