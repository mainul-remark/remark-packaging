<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_weight_specs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('version');
            $table->decimal('reference_weight_g', 14, 3);
            $table->decimal('lower_tolerance_g', 14, 3)->default(0);
            $table->decimal('upper_tolerance_g', 14, 3)->default(0);
            $table->string('measurement_method', 32);
            $table->string('verification_status', 24)->default('PENDING');
            $table->foreignId('approved_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestampTz('approved_at')->nullable();
            $table->timestampTz('effective_from');
            $table->timestampTz('effective_to')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->unique(['product_id', 'version']);
            $table->index(['product_id', 'verification_status', 'effective_from'], 'weight_spec_status_effective_idx');
        });

        Schema::create('product_weight_samples', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_weight_spec_id')->constrained()->restrictOnDelete();
            $table->decimal('measured_weight_g', 14, 3);
            $table->unsignedBigInteger('scale_id')->nullable();
            $table->string('measurement_source', 24);
            $table->foreignId('measured_by')->constrained('users')->restrictOnDelete();
            $table->timestampTz('measured_at');
            $table->timestampTz('created_at')->useCurrent();
            $table->index(['product_weight_spec_id', 'measured_at']);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE product_weight_specs ADD COLUMN current_approved_product_id BIGINT UNSIGNED GENERATED ALWAYS AS (IF(verification_status = 'VERIFIED' AND effective_to IS NULL, product_id, NULL)) STORED");
            DB::statement('CREATE UNIQUE INDEX product_weight_specs_one_current_approved ON product_weight_specs (current_approved_product_id)');
        } else {
            DB::statement("CREATE UNIQUE INDEX product_weight_specs_one_current_approved ON product_weight_specs (product_id) WHERE verification_status = 'VERIFIED' AND effective_to IS NULL");
        }

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE product_weight_specs ADD CONSTRAINT product_weight_specs_reference_positive CHECK (reference_weight_g > 0)');
            DB::statement('ALTER TABLE product_weight_specs ADD CONSTRAINT product_weight_specs_tolerances_nonnegative CHECK (lower_tolerance_g >= 0 AND upper_tolerance_g >= 0)');
            DB::statement('ALTER TABLE product_weight_specs ADD CONSTRAINT product_weight_specs_effective_period_check CHECK (effective_to IS NULL OR effective_to > effective_from)');
            DB::statement("ALTER TABLE product_weight_specs ADD CONSTRAINT product_weight_specs_method_check CHECK (measurement_method IN ('UNIT_SAMPLE', 'DECLARED', 'SCALE_CAPTURED'))");
            DB::statement("ALTER TABLE product_weight_specs ADD CONSTRAINT product_weight_specs_status_check CHECK (verification_status IN ('PENDING', 'VERIFIED', 'REJECTED'))");
            DB::statement('ALTER TABLE product_weight_samples ADD CONSTRAINT product_weight_samples_nonnegative CHECK (measured_weight_g >= 0)');
            DB::statement("ALTER TABLE product_weight_samples ADD CONSTRAINT product_weight_samples_source_check CHECK (measurement_source IN ('SCALE', 'MANUAL', 'SOURCE_DECLARED'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_weight_samples');
        Schema::dropIfExists('product_weight_specs');
    }
};
