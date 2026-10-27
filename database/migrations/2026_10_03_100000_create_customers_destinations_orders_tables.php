<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Placeholder shapes: adjust these columns once the SAP API response format is known.
        Schema::create('customers', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 100)->unique();
            $table->string('name', 200);
            $table->timestampsTz();
        });

        Schema::create('destinations', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 100)->unique();
            $table->string('name', 200);
            $table->timestampsTz();
        });

        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->string('order_number', 120)->unique();
            $table->string('invoice_number', 120)->nullable();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('destination_id')->constrained()->restrictOnDelete();
            $table->date('order_date');
            $table->unsignedSmallInteger('priority')->default(3);
            $table->string('status', 24)->default('OPEN');
            $table->timestampsTz();
            $table->index(['status', 'order_date']);
        });

        Schema::create('order_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->string('external_line_number', 80)->nullable();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->string('original_sku', 100);
            $table->string('original_description', 500);
            $table->decimal('required_quantity', 14, 3);
            $table->string('uom', 24);
            $table->string('status', 24)->default('OPEN');
            $table->timestampsTz();
            $table->index(['order_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('destinations');
        Schema::dropIfExists('customers');
    }
};
