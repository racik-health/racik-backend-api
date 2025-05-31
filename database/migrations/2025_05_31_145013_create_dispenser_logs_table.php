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
        Schema::create('dispenser_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dispenser_id')->constrained()->onDelete('cascade');
            $table->foreignId('dispenser_slot_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('action_type', [
                'dispense_success',
                'dispense_failed',
                'refill',
                'error_report',
                'status_update',
                'command_received',
                'command_executed'
            ]);
            $table->json('details')->nullable();
            $table->softDeletes();
            $table->timestamp('timestamp')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dispenser_logs');
    }
};
