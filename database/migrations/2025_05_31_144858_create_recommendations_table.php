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
        Schema::create('recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('analysis_id')->unique()->constrained()->onDelete('cascade');
            $table->string('recommended_herbal_medicine');
            $table->text('recommendation_description')->nullable();
            $table->json('herbal_medicine_details')->nullable();
            $table->decimal('ai_confidence_level', 5, 2)->nullable();
            $table->json('raw_flask_response')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recommendations');
    }
};
