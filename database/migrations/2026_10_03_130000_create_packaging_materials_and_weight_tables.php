<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('packaging_materials', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 64)->unique();
            $table->string('name', 160);
            $table->decimal('reference_weight_g', 14, 3);
            $table->decimal('tolerance_g', 14, 3)->default(0);
            $table->string('status', 16)->default('ACTIVE');
            $table->string('image_path', 255)->nullable();
            $table->timestampTz('image_updated_at')->nullable();
            $table->timestampsTz();
        });

        // Current materials on a carton, with the weight and tolerance frozen when they were set.
        Schema::create('carton_materials', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('carton_id')->constrained()->restrictOnDelete();
            $table->foreignId('material_id')->constrained('packaging_materials')->restrictOnDelete();
            $table->decimal('quantity', 14, 3);
            $table->decimal('unit_weight_snapshot_g', 14, 3);
            $table->decimal('unit_tolerance_snapshot_g', 14, 3)->default(0);
            $table->decimal('total_weight_g', 14, 3);
            $table->timestampsTz();
            $table->unique(['carton_id', 'material_id']);
        });

        // Append-only: every attempt, passing or failing, with the exact inputs used.
        Schema::create('weight_measurements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('carton_id')->constrained()->restrictOnDelete();
            $table->decimal('expected_weight_g', 14, 3);
            $table->decimal('measured_weight_g', 14, 3);
            $table->decimal('lower_limit_g', 14, 3);
            $table->decimal('upper_limit_g', 14, 3);
            $table->decimal('difference_g', 14, 3);
            $table->string('result', 16);
            $table->string('measurement_source', 16);
            $table->json('calculation_inputs');
            $table->foreignId('operator_id')->constrained('users')->restrictOnDelete();
            $table->uuid('idempotency_key')->unique();
            $table->timestampTz('measured_at');
            $table->timestampTz('created_at')->useCurrent();
            $table->index(['carton_id', 'measured_at']);
        });

        // The formally accepted outcome: one passing attempt, or a reasoned supervisor override of a failed one.
        Schema::create('weight_verifications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('carton_id')->constrained()->restrictOnDelete();
            $table->foreignId('weight_measurement_id')->constrained('weight_measurements')->restrictOnDelete();
            $table->string('method', 24);
            $table->string('override_reason', 500)->nullable();
            $table->foreignId('accepted_by')->constrained('users')->restrictOnDelete();
            $table->timestampTz('accepted_at');
            $table->string('status', 16)->default('ACTIVE');
            $table->timestampTz('invalidated_at')->nullable();
            $table->string('invalidation_reason', 500)->nullable();
            $table->timestampsTz();
            $table->index(['carton_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weight_verifications');
        Schema::dropIfExists('weight_measurements');
        Schema::dropIfExists('carton_materials');
        Schema::dropIfExists('packaging_materials');
    }
};
