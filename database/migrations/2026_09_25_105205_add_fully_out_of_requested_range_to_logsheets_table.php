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
        Schema::table('logsheets', function (Blueprint $table) {
            if (!Schema::hasColumn('logsheets', 'fully_out_of_requested_range')) {
                $table->boolean('fully_out_of_requested_range')->default(false)->after('last_import_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('logsheets', function (Blueprint $table) {
            if (Schema::hasColumn('logsheets', 'fully_out_of_requested_range')) {
                $table->dropColumn('fully_out_of_requested_range');
            }
        });
    }
};
