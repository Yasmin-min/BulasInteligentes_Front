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
        Schema::create('treatment_plan_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('treatment_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('medication_id')->nullable()->constrained('medications')->nullOnDelete();
            $table->string('medication_name');
            $table->string('dosage')->nullable();
            $table->string('route')->nullable();
            $table->text('instructions')->nullable();
            $table->unsignedInteger('interval_minutes')->nullable();
            $table->unsignedInteger('total_doses')->nullable();
            $table->unsignedInteger('duration_days')->nullable();
            $table->timestamp('first_dose_at')->nullable();
            $table->timestamp('last_calculated_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['treatment_plan_id']);
            $table->index('medication_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('treatment_plan_items');
    }
};
