<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->json('main_symptoms');
            $table->text('other_description')->nullable();
            $table->enum('severity_level', ['mild', 'moderate', 'severe']);
            $table->string('symptom_duration');
            $table->enum('status', ['pending_recommendation', 'completed', 'analysis_failed'])->default('pending_recommendation');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analyses');
    }
};
