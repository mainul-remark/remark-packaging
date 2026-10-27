<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_barcodes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->string('barcode', 128)->unique();
            $table->string('barcode_type', 24);
            $table->decimal('quantity_multiplier', 14, 3);
            $table->boolean('is_primary')->default(false);
            $table->string('status', 24)->default('ACTIVE');
            $table->timestampsTz();
            $table->index(['product_id', 'status']);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE product_barcodes ADD COLUMN active_primary_product_id BIGINT UNSIGNED GENERATED ALWAYS AS (IF(is_primary = 1 AND status = 'ACTIVE', product_id, NULL)) STORED");
            DB::statement('CREATE UNIQUE INDEX product_barcodes_one_primary_per_product ON product_barcodes (active_primary_product_id)');
        } else {
            DB::statement('CREATE UNIQUE INDEX product_barcodes_one_primary_per_product ON product_barcodes (product_id) WHERE is_primary = TRUE AND status = \'ACTIVE\'');
        }

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE product_barcodes ADD CONSTRAINT product_barcodes_type_check CHECK (barcode_type IN ('EAN', 'UPC', 'GTIN', 'CODE128', 'INTERNAL'))");
            DB::statement("ALTER TABLE product_barcodes ADD CONSTRAINT product_barcodes_status_check CHECK (status IN ('ACTIVE', 'INACTIVE'))");
            DB::statement('ALTER TABLE product_barcodes ADD CONSTRAINT product_barcodes_multiplier_positive CHECK (quantity_multiplier > 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_barcodes');
    }
};
