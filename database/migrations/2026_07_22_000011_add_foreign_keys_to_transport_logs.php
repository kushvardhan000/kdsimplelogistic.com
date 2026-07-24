<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transport_logs', function (Blueprint $table) {
            $table->foreign('vehicle_id')->references('id')->on('vehicles')->nullOnDelete();
            $table->foreign('company_id')->references('id')->on('companies')->nullOnDelete();
            $table->foreign('carrier_id')->references('id')->on('companies')->nullOnDelete();
            $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
            $table->foreign('fuel_station_id')->references('id')->on('fuel_stations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('transport_logs', function (Blueprint $table) {
            $table->dropForeign(['vehicle_id']);
            $table->dropForeign(['carrier_id']);
            $table->dropForeign(['company_id']);
            $table->dropForeign(['branch_id']);
            $table->dropForeign(['fuel_station_id']);
        });
    }
};
