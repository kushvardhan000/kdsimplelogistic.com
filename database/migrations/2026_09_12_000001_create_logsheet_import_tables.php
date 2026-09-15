<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logsheet_imports', function (Blueprint $table) {
            $table->id();
            $table->date('date_from')->nullable();
            $table->date('date_to')->nullable();
            $table->string('original_filename');
            $table->foreignId('uploaded_by')->nullable()->constrained('users');
            $table->unsignedInteger('row_count')->default(0);
            $table->unsignedInteger('consolidated_count')->default(0);
            $table->unsignedInteger('duplicate_count')->default(0);
            $table->unsignedInteger('invalid_count')->default(0);
            $table->string('status')->default('pending');
            $table->timestamps();
        });

        Schema::create('logsheet_raw_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_id')->constrained('logsheet_imports');
            $table->string('log_sheet_no')->nullable()->index();
            $table->json('raw_data');
            $table->unsignedInteger('row_number_in_file');
            $table->boolean('is_valid')->default(true);
            $table->text('validation_error')->nullable();
            $table->timestamps();
        });

        Schema::create('logsheets', function (Blueprint $table) {
            $table->id();
            $table->string('log_sheet_no')->unique()->index();
            $table->date('date')->nullable();
            $table->string('vehicle_no')->nullable();
            $table->string('tprt_code')->nullable();
            $table->string('tprt_name')->nullable();
            $table->string('destination')->nullable();
            $table->string('sap_invoice_no')->nullable();
            $table->date('posting_date')->nullable();
            $table->date('bill_date')->nullable();
            $table->string('vendor_inv_no')->nullable();
            $table->decimal('total_gross_wt', 12, 3)->default(0);
            $table->decimal('total_booked_amount', 14, 2)->default(0);
            $table->decimal('total_actual_amount', 14, 2)->default(0);
            $table->decimal('total_diff', 14, 2)->default(0);
            $table->unsignedInteger('consignment_count')->default(0);
            $table->string('status')->default('pending');
            $table->dateTime('cleared_at')->nullable();
            $table->foreignId('cleared_by')->nullable()->constrained('users');
            $table->foreignId('last_import_id')->nullable()->constrained('logsheet_imports');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('logsheet_clearings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('logsheet_id')->constrained('logsheets');
            $table->foreignId('cleared_by')->constrained('users');
            $table->dateTime('cleared_at');
            $table->string('invoice_no_reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logsheet_clearings');
        Schema::dropIfExists('logsheets');
        Schema::dropIfExists('logsheet_raw_rows');
        Schema::dropIfExists('logsheet_imports');
    }
};
