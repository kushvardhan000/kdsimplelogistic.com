<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transport_logs', function (Blueprint $table) {
            $table->index('created_at');
            $table->index('clearing_date');
            $table->index(['created_at', 'profit']);
            $table->index(['created_at', 'company']);
        });
    }

    public function down(): void
    {
        Schema::table('transport_logs', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
            $table->dropIndex(['clearing_date']);
            $table->dropIndex(['created_at', 'profit']);
            $table->dropIndex(['created_at', 'company']);
        });
    }
};
