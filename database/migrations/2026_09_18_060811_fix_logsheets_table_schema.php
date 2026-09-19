<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('logsheets', function (Blueprint $table) {
            // Drop columns from the incorrect migration (2026_09_15_125234)
            if (Schema::hasColumn('logsheets', 'date_from')) {
                $table->dropColumn('date_from');
            }
            if (Schema::hasColumn('logsheets', 'date_to')) {
                $table->dropColumn('date_to');
            }
            if (Schema::hasColumn('logsheets', 'original_filename')) {
                $table->dropColumn('original_filename');
            }
            if (Schema::hasColumn('logsheets', 'file_path')) {
                $table->dropColumn('file_path');
            }
            if (Schema::hasColumn('logsheets', 'final_amount')) {
                $table->dropColumn('final_amount');
            }
            if (Schema::hasColumn('logsheets', 'total_rows')) {
                $table->dropColumn('total_rows');
            }
            if (Schema::hasColumn('logsheets', 'uploaded_by')) {
                $table->dropColumn('uploaded_by');
            }
        });

        Schema::table('logsheets', function (Blueprint $table) {
            // Add missing columns from the correct schema (2026_09_12_000001)
            if (!Schema::hasColumn('logsheets', 'log_sheet_no')) {
                $table->string('log_sheet_no')->unique()->index()->after('id');
            }
            if (!Schema::hasColumn('logsheets', 'date')) {
                $table->date('date')->nullable()->after('log_sheet_no');
            }
            if (!Schema::hasColumn('logsheets', 'vehicle_no')) {
                $table->string('vehicle_no')->nullable()->after('date');
            }
            if (!Schema::hasColumn('logsheets', 'tprt_code')) {
                $table->string('tprt_code')->nullable()->after('vehicle_no');
            }
            if (!Schema::hasColumn('logsheets', 'tprt_name')) {
                $table->string('tprt_name')->nullable()->after('tprt_code');
            }
            if (!Schema::hasColumn('logsheets', 'destination')) {
                $table->string('destination')->nullable()->after('tprt_name');
            }
            if (!Schema::hasColumn('logsheets', 'sap_invoice_no')) {
                $table->string('sap_invoice_no')->nullable()->after('destination');
            }
            if (!Schema::hasColumn('logsheets', 'posting_date')) {
                $table->date('posting_date')->nullable()->after('sap_invoice_no');
            }
            if (!Schema::hasColumn('logsheets', 'bill_date')) {
                $table->date('bill_date')->nullable()->after('posting_date');
            }
            if (!Schema::hasColumn('logsheets', 'vendor_inv_no')) {
                $table->string('vendor_inv_no')->nullable()->after('bill_date');
            }
            if (!Schema::hasColumn('logsheets', 'total_gross_wt')) {
                $table->decimal('total_gross_wt', 12, 3)->default(0)->after('vendor_inv_no');
            }
            if (!Schema::hasColumn('logsheets', 'total_booked_amount')) {
                $table->decimal('total_booked_amount', 14, 2)->default(0)->after('total_gross_wt');
            }
            if (!Schema::hasColumn('logsheets', 'total_actual_amount')) {
                $table->decimal('total_actual_amount', 14, 2)->default(0)->after('total_booked_amount');
            }
            if (!Schema::hasColumn('logsheets', 'total_diff')) {
                $table->decimal('total_diff', 14, 2)->default(0)->after('total_actual_amount');
            }
            if (!Schema::hasColumn('logsheets', 'consignment_count')) {
                $table->unsignedInteger('consignment_count')->default(0)->after('total_diff');
            }
            if (!Schema::hasColumn('logsheets', 'status')) {
                $table->string('status')->default('pending')->after('consignment_count');
            }
            if (!Schema::hasColumn('logsheets', 'cleared_at')) {
                $table->dateTime('cleared_at')->nullable()->after('status');
            }
            if (!Schema::hasColumn('logsheets', 'cleared_by')) {
                $table->foreignId('cleared_by')->nullable()->constrained('users')->nullOnDelete()->after('cleared_at');
            }
            if (!Schema::hasColumn('logsheets', 'last_import_id')) {
                $table->foreignId('last_import_id')->nullable()->constrained('logsheet_imports')->nullOnDelete()->after('cleared_by');
            }
            if (!Schema::hasColumn('logsheets', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        // Fix column types if they exist but with wrong type
        $this->fixColumnTypes();
    }

    protected function fixColumnTypes(): void
    {
        Schema::table('logsheets', function (Blueprint $table) {
            // Ensure correct column types
            if (Schema::hasColumn('logsheets', 'total_gross_wt')) {
                $table->decimal('total_gross_wt', 12, 3)->default(0)->change();
            }
            if (Schema::hasColumn('logsheets', 'total_booked_amount')) {
                $table->decimal('total_booked_amount', 14, 2)->default(0)->change();
            }
            if (Schema::hasColumn('logsheets', 'total_actual_amount')) {
                $table->decimal('total_actual_amount', 14, 2)->default(0)->change();
            }
            if (Schema::hasColumn('logsheets', 'total_diff')) {
                $table->decimal('total_diff', 14, 2)->default(0)->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('logsheets', function (Blueprint $table) {
            // Re-add the columns from the incorrect migration
            $table->date('date_from')->nullable();
            $table->date('date_to')->nullable();
            $table->string('original_filename')->nullable();
            $table->string('file_path')->nullable();
            $table->decimal('final_amount', 14, 2)->default(0);
            $table->unsignedInteger('total_rows')->default(0);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();

            // Drop the correct schema columns
            $table->dropColumn([
                'log_sheet_no',
                'date',
                'vehicle_no',
                'tprt_code',
                'tprt_name',
                'destination',
                'sap_invoice_no',
                'posting_date',
                'bill_date',
                'vendor_inv_no',
                'total_gross_wt',
                'total_booked_amount',
                'total_actual_amount',
                'total_diff',
                'consignment_count',
                'status',
                'cleared_at',
                'cleared_by',
                'last_import_id',
                'deleted_at',
            ]);
        });
    }
};