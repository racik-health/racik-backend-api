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
        Schema::create('consumption_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('herbal_medicine_name');
            $table->foreignId('recommendation_id')->nullable()->constrained()->onDelete('set null');
            $table->dateTime('consumed_at');
            $table->string('quantity');
            $table->string('unit');
            $table->text('notes')->nullable();
            $table->enum('source', ['ai_recommendation', 'manual_input', 'dispenser'])->default('dispenser');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consumption_logs');
    }
};
