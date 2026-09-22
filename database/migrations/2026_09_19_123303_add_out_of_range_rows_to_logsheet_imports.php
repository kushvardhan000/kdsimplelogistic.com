<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('logsheet_imports', function (Blueprint $table) {
            if (!Schema::hasColumn('logsheet_imports', 'out_of_range_rows')) {
                $table->unsignedInteger('out_of_range_rows')->default(0)->after('total_gross_wt');
            }
        });
    }

    public function down(): void
    {
        Schema::table('logsheet_imports', function (Blueprint $table) {
            if (Schema::hasColumn('logsheet_imports', 'out_of_range_rows')) {
                $table->dropColumn('out_of_range_rows');
            }
        });
    }
};
