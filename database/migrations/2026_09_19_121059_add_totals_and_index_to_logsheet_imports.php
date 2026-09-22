<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('logsheet_imports', function (Blueprint $table) {
            if (!Schema::hasColumn('logsheet_imports', 'total_amount')) {
                $table->decimal('total_amount', 16, 2)->default(0)->after('status');
            }
            if (!Schema::hasColumn('logsheet_imports', 'total_booked_amount')) {
                $table->decimal('total_booked_amount', 16, 2)->default(0)->after('total_amount');
            }
            if (!Schema::hasColumn('logsheet_imports', 'total_diff')) {
                $table->decimal('total_diff', 16, 2)->default(0)->after('total_booked_amount');
            }
            if (!Schema::hasColumn('logsheet_imports', 'total_gross_wt')) {
                $table->decimal('total_gross_wt', 16, 3)->default(0)->after('total_diff');
            }
            if (!Schema::hasColumn('logsheet_imports', 'out_of_range_rows')) {
                $table->unsignedInteger('out_of_range_rows')->default(0)->after('total_gross_wt');
            }
        });

        if (!Schema::hasIndex('logsheet_imports', 'logsheet_imports_date_from_date_to_index')) {
            Schema::table('logsheet_imports', function (Blueprint $table) {
                $table->index(['date_from', 'date_to'], 'logsheet_imports_date_from_date_to_index');
            });
        }

        // Backfill existing imports by summing their logsheets via last_import_id
        $imports = DB::table('logsheet_imports')->get();
        foreach ($imports as $import) {
            $sums = DB::table('logsheets')
                ->where('last_import_id', $import->id)
                ->selectRaw('
                    COALESCE(SUM(total_actual_amount), 0) as total_amount,
                    COALESCE(SUM(total_booked_amount), 0) as total_booked_amount,
                    COALESCE(SUM(total_diff), 0) as total_diff,
                    COALESCE(SUM(total_gross_wt), 0) as total_gross_wt
                ')
                ->first();

            DB::table('logsheet_imports')
                ->where('id', $import->id)
                ->update([
                    'total_amount' => $sums->total_amount ?? 0,
                    'total_booked_amount' => $sums->total_booked_amount ?? 0,
                    'total_diff' => $sums->total_diff ?? 0,
                    'total_gross_wt' => $sums->total_gross_wt ?? 0,
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('logsheet_imports', function (Blueprint $table) {
            if (Schema::hasIndex('logsheet_imports', 'logsheet_imports_date_from_date_to_index')) {
                $table->dropIndex('logsheet_imports_date_from_date_to_index');
            }
            if (Schema::hasColumn('logsheet_imports', 'out_of_range_rows')) {
                $table->dropColumn('out_of_range_rows');
            }
            if (Schema::hasColumn('logsheet_imports', 'total_gross_wt')) {
                $table->dropColumn('total_gross_wt');
            }
            if (Schema::hasColumn('logsheet_imports', 'total_diff')) {
                $table->dropColumn('total_diff');
            }
            if (Schema::hasColumn('logsheet_imports', 'total_booked_amount')) {
                $table->dropColumn('total_booked_amount');
            }
            if (Schema::hasColumn('logsheet_imports', 'total_amount')) {
                $table->dropColumn('total_amount');
            }
        });
    }
};
