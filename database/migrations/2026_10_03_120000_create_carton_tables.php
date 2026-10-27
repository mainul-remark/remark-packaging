<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carton_types', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 64);
            $table->string('name', 160);
            $table->decimal('tare_weight_g', 14, 3)->default(0);
            $table->decimal('tare_tolerance_g', 14, 3)->default(0);
            $table->decimal('max_gross_weight_g', 14, 3)->nullable();
            $table->unsignedInteger('length_mm')->nullable();
            $table->unsignedInteger('width_mm')->nullable();
            $table->unsignedInteger('height_mm')->nullable();
            $table->string('status', 16)->default('ACTIVE');
            $table->string('image_path', 255)->nullable();
            $table->timestampTz('image_updated_at')->nullable();
            $table->timestampsTz();
        });

        Schema::create('cartons', function (Blueprint $table): void {
            $table->id();
            $table->string('carton_number', 150);
            $table->string('box_number', 32)->nullable();
            $table->string('sscc', 18)->nullable()->unique();
            $table->string('seal_number', 80)->nullable()->unique();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->foreignId('carton_type_id')->nullable()->constrained('carton_types')->restrictOnDelete();
            $table->string('packaging_mode', 16);
            $table->string('status', 32)->default('CREATED');
            $table->decimal('expected_weight_g', 14, 3)->nullable();
            $table->decimal('verified_weight_g', 14, 3)->nullable();
            $table->foreignId('packed_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('verified_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestampTz('sealed_at')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->timestampsTz();
            $table->index(['order_id', 'status']);
        });

        Schema::create('carton_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('carton_id')->constrained()->restrictOnDelete();
            $table->foreignId('order_item_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('weight_spec_id')->nullable()->constrained('product_weight_specs')->restrictOnDelete();
            $table->decimal('quantity', 14, 3)->default(0);
            $table->decimal('unit_weight_snapshot_g', 14, 3)->nullable();
            $table->timestampsTz();
            $table->unique(['carton_id', 'order_item_id']);
            $table->index('order_item_id');
        });

        // Append-only: how carton contents changed. Never updated or deleted.
        Schema::create('carton_item_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('carton_id')->constrained()->restrictOnDelete();
            $table->foreignId('carton_item_id')->nullable()->constrained('carton_items')->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('order_item_id')->constrained()->restrictOnDelete();
            $table->string('scanned_barcode', 128)->nullable();
            $table->decimal('quantity_delta', 14, 3);
            $table->string('event_type', 16);
            $table->foreignId('operator_id')->constrained('users')->restrictOnDelete();
            $table->uuid('idempotency_key')->unique();
            $table->timestampTz('occurred_at');
            $table->timestampTz('created_at')->useCurrent();
            $table->index(['carton_id', 'occurred_at']);
            $table->index('order_item_id');
        });

        Schema::create('carton_status_history', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('carton_id')->constrained()->restrictOnDelete();
            $table->string('from_status', 32)->nullable();
            $table->string('to_status', 32);
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->string('reason', 500)->nullable();
            $table->timestampTz('occurred_at');
            $table->index(['carton_id', 'occurred_at']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE carton_items ADD CONSTRAINT carton_items_quantity_nonnegative CHECK (quantity >= 0)');
            DB::statement('ALTER TABLE carton_item_events ADD CONSTRAINT carton_item_events_delta_nonzero CHECK (quantity_delta <> 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('carton_status_history');
        Schema::dropIfExists('carton_item_events');
        Schema::dropIfExists('carton_items');
        Schema::dropIfExists('cartons');
        Schema::dropIfExists('carton_types');
    }
};
