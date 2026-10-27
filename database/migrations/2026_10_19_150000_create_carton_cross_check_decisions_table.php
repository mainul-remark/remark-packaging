<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The security checker's final call on a carton: cleared for dispatch or flagged for review. It never changes the carton's status.
        Schema::create('carton_cross_check_decisions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('carton_id')->constrained()->restrictOnDelete();
            $table->string('decision', 16);
            $table->boolean('label_matched');
            $table->boolean('seal_intact');
            $table->boolean('condition_acceptable');
            $table->text('note')->nullable();
            $table->decimal('recorded_weight_g', 14, 3)->nullable();
            $table->decimal('checker_weight_g', 14, 3)->nullable();
            $table->decimal('difference_g', 14, 3)->nullable();
            $table->decimal('tolerance_g', 14, 3)->default(0);
            $table->string('direction', 16)->nullable();
            $table->foreignId('decided_by')->constrained('users')->restrictOnDelete();
            $table->timestampTz('decided_at', 6);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carton_cross_check_decisions');
    }
};
