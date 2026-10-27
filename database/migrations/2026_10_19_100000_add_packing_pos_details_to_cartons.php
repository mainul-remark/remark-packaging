<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Everything the packing POS knows about a carton when it is created, frozen at that moment:
        // the system-computed weight and its governed limits, the operator's machine and manual readings,
        // and the batch the carton came from.
        Schema::table('cartons', function (Blueprint $table): void {
            $table->string('creation_channel', 24)->nullable()->after('packaging_mode');
            $table->decimal('unit_count', 14, 3)->nullable()->after('expected_weight_g');
            $table->decimal('system_weight_g', 14, 3)->nullable()->after('unit_count');
            $table->decimal('weight_tolerance_lower_g', 14, 3)->nullable()->after('system_weight_g');
            $table->decimal('weight_tolerance_upper_g', 14, 3)->nullable()->after('weight_tolerance_lower_g');
            $table->decimal('weight_lower_limit_g', 14, 3)->nullable()->after('weight_tolerance_upper_g');
            $table->decimal('weight_upper_limit_g', 14, 3)->nullable()->after('weight_lower_limit_g');
            $table->decimal('machine_weight_g', 14, 3)->nullable()->after('weight_upper_limit_g');
            $table->decimal('manual_weight_g', 14, 3)->nullable()->after('machine_weight_g');
            $table->json('weight_breakdown')->nullable()->after('manual_weight_g');
            $table->string('draft_reference', 16)->nullable()->after('weight_breakdown');
            $table->uuid('batch_key')->nullable()->after('draft_reference');
            $table->unsignedSmallInteger('batch_sequence')->nullable()->after('batch_key');
            $table->unique(['batch_key', 'batch_sequence']);
        });
    }

    public function down(): void
    {
        Schema::table('cartons', function (Blueprint $table): void {
            $table->dropUnique(['batch_key', 'batch_sequence']);
            $table->dropColumn(['creation_channel', 'unit_count', 'system_weight_g', 'weight_tolerance_lower_g', 'weight_tolerance_upper_g', 'weight_lower_limit_g', 'weight_upper_limit_g', 'machine_weight_g', 'manual_weight_g', 'weight_breakdown', 'draft_reference', 'batch_key', 'batch_sequence']);
        });
    }
};
