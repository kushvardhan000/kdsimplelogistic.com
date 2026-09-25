<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('logsheet_imports', function (Blueprint $table) {
            if (!Schema::hasColumn('logsheet_imports', 'skipped_out_of_range_groups')) {
                $table->unsignedInteger('skipped_out_of_range_groups')->default(0)->after('out_of_range_rows');
            }
        });
    }

    public function down(): void
    {
        Schema::table('logsheet_imports', function (Blueprint $table) {
            if (Schema::hasColumn('logsheet_imports', 'skipped_out_of_range_groups')) {
                $table->dropColumn('skipped_out_of_range_groups');
            }
        });
    }
};