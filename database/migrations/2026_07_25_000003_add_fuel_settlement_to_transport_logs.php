<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transport_logs', function (Blueprint $table) {
            $table->decimal('fuel_paid_amount', 14, 2)->default(0)->after('fuel_station_balance');
            $table->enum('fuel_payment_status', ['unpaid', 'partial', 'paid', 'overpaid'])->default('unpaid')->after('fuel_paid_amount');
        });
    }

    public function down(): void
    {
        Schema::table('transport_logs', function (Blueprint $table) {
            $table->dropColumn(['fuel_payment_status', 'fuel_paid_amount']);
        });
    }
};
