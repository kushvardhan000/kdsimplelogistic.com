<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('vehicle_no')->unique();
            $table->string('owner_name')->nullable();
            $table->string('type')->nullable();
            $table->decimal('capacity_kg', 12, 2)->nullable();
            $table->decimal('mileage_baseline', 12, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index('vehicle_no');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
