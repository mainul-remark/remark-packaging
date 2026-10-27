<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('label_print_jobs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('carton_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('copy_number');
            $table->string('format', 16)->default('QR');
            $table->boolean('is_reprint')->default(false);
            $table->string('reprint_reason', 500)->nullable();
            $table->json('label_snapshot');
            $table->foreignId('printed_by')->constrained('users')->restrictOnDelete();
            $table->timestampTz('printed_at');
            $table->timestampTz('created_at')->useCurrent();
            $table->unique(['carton_id', 'copy_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('label_print_jobs');
    }
};
