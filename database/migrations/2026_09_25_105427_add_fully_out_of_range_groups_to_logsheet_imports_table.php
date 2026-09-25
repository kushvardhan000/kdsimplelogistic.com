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
        Schema::table('logsheet_imports', function (Blueprint $table) {
            if (!Schema::hasColumn('logsheet_imports', 'fully_out_of_range_groups')) {
                $table->unsignedInteger('fully_out_of_range_groups')->default(0)->after('skipped_out_of_range_groups');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('logsheet_imports', function (Blueprint $table) {
            if (Schema::hasColumn('logsheet_imports', 'fully_out_of_range_groups')) {
                $table->dropColumn('fully_out_of_range_groups');
            }
        });
    }
};
