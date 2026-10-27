<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_categories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('parent_category_id')->nullable();
            $table->string('code', 64);
            $table->integer('sap_id')->nullable();
            $table->string('name', 160);
            $table->string('status', 24)->default('ACTIVE');
            $table->timestampsTz();
        });

        Schema::create('brands', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('parent_brand_id')->nullable();
            $table->string('code', 64);
            $table->integer('sap_id')->nullable();
            $table->string('name', 160);
            $table->string('status', 24)->default('ACTIVE');
            $table->timestampsTz();
        });

        Schema::create('variants', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 64);
            $table->integer('sap_id')->nullable();
            $table->string('name', 160);
            $table->string('status', 24)->default('ACTIVE');
            $table->timestampsTz();
        });

        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('category_id')->nullable();
            $table->foreignId('brand_id')->nullable();
            $table->foreignId('variant_id')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('sku', 100)->nullable();
//            $table->string('external_material_code', 100)->nullable();
            $table->string('name', 200);
            $table->text('description')->nullable();
            $table->string('base_uom', 24)->default('PCS');
            $table->integer('total_shelf_life')->nullable();
            $table->mediumInteger('default_carton_size')->nullable();
            $table->string('image_path')->nullable();
            $table->boolean('requires_batch')->default(false);
            $table->boolean('requires_expiry')->default(false);
            $table->string('verification_status', 24)->default('PROVISIONAL');
            $table->timestampTz('verified_at')->nullable();
            $table->string('status', 24)->default('ACTIVE');
            $table->timestampsTz();
        });



        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE product_categories ADD CONSTRAINT product_categories_status_check CHECK (status IN ('ACTIVE', 'INACTIVE'))");
            DB::statement("ALTER TABLE products ADD CONSTRAINT products_verification_status_check CHECK (verification_status IN ('PROVISIONAL', 'PENDING', 'VERIFIED', 'REJECTED'))");
            DB::statement("ALTER TABLE products ADD CONSTRAINT products_status_check CHECK (status IN ('ACTIVE', 'INACTIVE'))");
            DB::statement('ALTER TABLE products ADD CONSTRAINT products_required_expiry_needs_batch CHECK (NOT requires_expiry OR requires_batch)');
        }

        Schema::create('product_verification_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('operator_id')->constrained('users')->restrictOnDelete();
            $table->string('previous_status', 24);
            $table->string('new_status', 24);
            $table->text('reason')->nullable();
            $table->timestampTz('occurred_at');
            $table->timestampTz('created_at')->useCurrent();
            $table->index(['product_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_verification_events');
        Schema::dropIfExists('products');
        Schema::dropIfExists('product_categories');
        Schema::dropIfExists('brands');
        Schema::dropIfExists('variants');
    }
};
