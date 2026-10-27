<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A carton whose entered weight (manual, else machine) falls outside its allowed range can still be saved
        // when the operator confirms it; it is then flagged so supervisors can see it.
        Schema::table('cartons', function (Blueprint $table): void {
            $table->string('weight_flag', 16)->nullable()->after('manual_weight_g');
            $table->decimal('weight_variance_g', 14, 3)->nullable()->after('weight_flag');
            $table->foreignId('weight_flag_acknowledged_by')->nullable()->after('weight_variance_g')->constrained('users')->restrictOnDelete();
            $table->timestampTz('weight_flag_acknowledged_at')->nullable()->after('weight_flag_acknowledged_by');
        });
    }

    public function down(): void
    {
        Schema::table('cartons', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('weight_flag_acknowledged_by');
            $table->dropColumn(['weight_flag', 'weight_variance_g', 'weight_flag_acknowledged_at']);
        });
    }
};
