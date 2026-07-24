<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fuel_stations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('contact_info')->nullable();
            $table->text('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fuel_stations');
    }
};
