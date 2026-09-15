<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_field_options', function (Blueprint $table) {
            $table->id();
            $table->string('field_key')->index();
            $table->string('label');
            $table->string('value');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['field_key', 'value']);
            $table->index(['field_key', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_field_options');
    }
};
