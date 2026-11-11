<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table): void {
            $table->string('code', 2)->primary();
            $table->string('name');
        });

        Schema::create('provinces', function (Blueprint $table): void {
            $table->string('code', 4)->primary();
            $table->string('department_code', 2);
            $table->string('name');
            $table->foreign('department_code')->references('code')->on('departments')->restrictOnDelete();
        });

        Schema::create('districts', function (Blueprint $table): void {
            $table->string('code', 6)->primary();
            $table->string('province_code', 4);
            $table->string('name');
            $table->foreign('province_code')->references('code')->on('provinces')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('districts');
        Schema::dropIfExists('provinces');
        Schema::dropIfExists('departments');
    }
};
