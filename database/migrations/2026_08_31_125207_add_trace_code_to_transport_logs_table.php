<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transport_logs', function (Blueprint $table) {
            $table->string('trace_code', 20)->nullable()->unique()->after('logsheet_no');
        });
    }

    public function down(): void
    {
        Schema::table('transport_logs', function (Blueprint $table) {
            $table->dropColumn('trace_code');
        });
    }
};
