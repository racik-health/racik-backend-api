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
        Schema::create('dispenser_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dispenser_id')->constrained()->onDelete('cascade');
            $table->integer('slot_number');
            $table->string('available_herbal_medicine')->nullable();
            $table->decimal('available_quantity', 8, 2)->nullable();
            $table->string('available_unit')->nullable();
            $table->timestamp('last_refilled_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['dispenser_id', 'slot_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dispenser_slots');
    }
};
