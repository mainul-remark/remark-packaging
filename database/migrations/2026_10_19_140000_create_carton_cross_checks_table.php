<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per security re-weigh. The packed weights are snapshotted so the record stays meaningful if the carton is later changed.
        Schema::create('carton_cross_checks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('carton_id')->constrained()->restrictOnDelete();
            $table->decimal('verified_weight_g', 14, 3)->nullable();
            $table->decimal('manual_weight_g', 14, 3)->nullable();
            $table->decimal('machine_weight_g', 14, 3)->nullable();
            $table->decimal('recorded_weight_g', 14, 3)->nullable();
            $table->decimal('system_weight_g', 14, 3)->nullable();
            $table->decimal('checker_weight_g', 14, 3);
            $table->decimal('difference_g', 14, 3)->nullable();
            $table->decimal('tolerance_g', 14, 3)->default(0);
            $table->string('direction', 16);
            $table->text('note')->nullable();
            $table->foreignId('checked_by')->constrained('users')->restrictOnDelete();
            $table->timestampTz('checked_at', 6);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carton_cross_checks');
    }
};
