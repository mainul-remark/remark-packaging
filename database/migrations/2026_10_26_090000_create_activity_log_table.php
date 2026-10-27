<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection(config('activitylog.database_connection'))
            ->create(config('activitylog.table_name'), function (Blueprint $table): void {
                $table->id();
                $table->string('log_name', 191)->nullable()->index();
                $table->text('description');
                $table->unsignedBigInteger('subject_id')->nullable();
                $table->string('subject_type', 191)->nullable();
                $table->index(['subject_id', 'subject_type'], 'subject');
                $table->string('event')->nullable();
                $table->unsignedBigInteger('causer_id')->nullable();
                $table->string('causer_type', 191)->nullable();
                $table->index(['causer_id', 'causer_type'], 'causer');
                $table->json('properties')->nullable();
                $table->uuid('batch_uuid')->nullable();
                $table->timestamps();
            });
    }

    public function down(): void
    {
        Schema::connection(config('activitylog.database_connection'))
            ->dropIfExists(config('activitylog.table_name'));
    }
};
