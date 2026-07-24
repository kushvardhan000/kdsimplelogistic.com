<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transport_logs', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('vehicle_no');
            $table->string('company');
            $table->string('transport_name');
            $table->string('logsheet_no')->nullable()->unique();
            $table->string('destination')->nullable();
            $table->decimal('km', 12, 2)->default(0);
            $table->decimal('weight', 12, 2)->default(0);
            $table->decimal('to_bb_sale', 14, 2)->default(0);
            $table->decimal('paid_sale', 14, 2)->default(0);
            $table->decimal('to_pay', 14, 2)->default(0);
            $table->decimal('total_sale', 14, 2)->default(0);
            $table->decimal('freight', 14, 2)->default(0);
            $table->decimal('loading', 14, 2)->default(0);
            $table->decimal('unloading', 14, 2)->default(0);
            $table->decimal('dd', 14, 2)->default(0);
            $table->decimal('tempu_expense', 14, 2)->default(0);
            $table->decimal('commission', 14, 2)->default(0);
            $table->decimal('total_expense', 14, 2)->default(0);
            $table->decimal('profit', 14, 2)->default(0);
            $table->decimal('diesel_advance', 14, 2)->default(0);
            $table->decimal('cash_advance', 14, 2)->default(0);
            $table->decimal('total_advance', 14, 2)->default(0);
            $table->decimal('payment', 14, 2)->default(0);
            $table->string('fuel_station_name')->nullable();
            $table->decimal('fuel_station_balance', 14, 2)->default(0);
            $table->decimal('balance_vehicle_payment', 14, 2)->default(0);
            $table->date('clearing_date')->nullable();
            $table->text('detail')->nullable();
            $table->text('remarks')->nullable();
            $table->decimal('mileage', 12, 2)->nullable();
            $table->decimal('dtg_office_expense', 14, 2)->default(0);

            $table->unsignedBigInteger('vehicle_id')->nullable()->index();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->unsignedBigInteger('carrier_id')->nullable()->index();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->unsignedBigInteger('fuel_station_id')->nullable()->index();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('date');
            $table->index('vehicle_no');
            $table->index('company');
            $table->index('transport_name');
            $table->index('logsheet_no');
            $table->index('created_by');
            $table->index('updated_by');
            $table->index(['date', 'company']);
            $table->index(['date', 'vehicle_no']);
        });

        $driver = DB::connection()->getDriverName();
        if (in_array($driver, ['mysql', 'mariadb'])) {
            DB::unprepared('ALTER TABLE transport_logs ADD CONSTRAINT chk_km CHECK (km >= 0)');
            DB::unprepared('ALTER TABLE transport_logs ADD CONSTRAINT chk_weight CHECK (weight >= 0)');
            DB::unprepared('ALTER TABLE transport_logs ADD CONSTRAINT chk_to_bb_sale CHECK (to_bb_sale >= 0)');
            DB::unprepared('ALTER TABLE transport_logs ADD CONSTRAINT chk_paid_sale CHECK (paid_sale >= 0)');
            DB::unprepared('ALTER TABLE transport_logs ADD CONSTRAINT chk_to_pay CHECK (to_pay >= 0)');
            DB::unprepared('ALTER TABLE transport_logs ADD CONSTRAINT chk_freight CHECK (freight >= 0)');
            DB::unprepared('ALTER TABLE transport_logs ADD CONSTRAINT chk_loading CHECK (loading >= 0)');
            DB::unprepared('ALTER TABLE transport_logs ADD CONSTRAINT chk_unloading CHECK (unloading >= 0)');
            DB::unprepared('ALTER TABLE transport_logs ADD CONSTRAINT chk_dd CHECK (dd >= 0)');
            DB::unprepared('ALTER TABLE transport_logs ADD CONSTRAINT chk_tempu_expense CHECK (tempu_expense >= 0)');
            DB::unprepared('ALTER TABLE transport_logs ADD CONSTRAINT chk_commission CHECK (commission >= 0)');
            DB::unprepared('ALTER TABLE transport_logs ADD CONSTRAINT chk_diesel_advance CHECK (diesel_advance >= 0)');
            DB::unprepared('ALTER TABLE transport_logs ADD CONSTRAINT chk_cash_advance CHECK (cash_advance >= 0)');
            DB::unprepared('ALTER TABLE transport_logs ADD CONSTRAINT chk_payment CHECK (payment >= 0)');
            DB::unprepared('ALTER TABLE transport_logs ADD CONSTRAINT chk_fuel_station_balance CHECK (fuel_station_balance >= 0)');
            DB::unprepared('ALTER TABLE transport_logs ADD CONSTRAINT chk_mileage CHECK (mileage >= 0 OR mileage IS NULL)');
            DB::unprepared('ALTER TABLE transport_logs ADD CONSTRAINT chk_dtg_office_expense CHECK (dtg_office_expense >= 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('transport_logs');
    }
};
