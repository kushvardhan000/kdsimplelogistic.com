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
        Schema::create('logsheet_details', function (Blueprint $table) {
    $table->id();

    // THIS is the cascade delete you asked about:
    $table->foreignId('logsheet_id')
        ->constrained()          // links to logsheets.id
        ->cascadeOnDelete();     // deleting the parent auto-deletes this row

    $table->string('log_sheet_no')->index();  // indexed = fast lookups when you search/clear by this
    $table->date('date')->nullable();
    $table->string('invoice_no')->nullable();
    $table->date('inv_date')->nullable();
    $table->string('payer')->nullable();
    $table->string('payer_name')->nullable();
    $table->string('town')->nullable();
    $table->decimal('gross_wt', 14, 3)->nullable();
    $table->decimal('difference', 14, 3)->nullable();
    $table->decimal('amount', 14, 2)->nullable();
    $table->decimal('volume', 14, 3)->nullable();
    $table->string('tprt_code')->nullable();
    $table->string('tprt_name')->nullable();
    $table->string('container_id')->nullable();
    $table->string('destination')->nullable();
    $table->string('sap_invoice_no')->nullable();
    $table->date('posting_date')->nullable();
    $table->date('bill_date')->nullable();
    $table->string('vendor_inv_no')->nullable();
    $table->string('route')->nullable();
    $table->string('town_2')->nullable();            // 2nd "Town" column in the sheet
    $table->decimal('gross_weight_2', 14, 3)->nullable(); // 2nd "Gross weight" column
    $table->decimal('booked_amount', 14, 2)->nullable();
    $table->decimal('actual_rate', 14, 2)->nullable();
    $table->decimal('actual_amount', 14, 2)->nullable();
    $table->decimal('diff', 14, 2)->nullable();

    $table->boolean('cleared')->default(false);       // the "cleared" flag you asked for

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logsheet_details');
    }
};
