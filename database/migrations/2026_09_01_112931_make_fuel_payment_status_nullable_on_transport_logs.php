<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'])) {
            DB::statement('ALTER TABLE transport_logs MODIFY COLUMN fuel_payment_status ENUM(\'unpaid\', \'partial\', \'paid\', \'overpaid\') NULL');
        } else {
            Schema::table('transport_logs', function (Blueprint $table) {
                $table->string('fuel_payment_status', 20)->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'])) {
            DB::statement('ALTER TABLE transport_logs MODIFY COLUMN fuel_payment_status ENUM(\'unpaid\', \'partial\', \'paid\', \'overpaid\') NOT NULL DEFAULT \'unpaid\'');
        } else {
            Schema::table('transport_logs', function (Blueprint $table) {
                $table->string('fuel_payment_status', 20)->nullable(false)->default('unpaid')->change();
            });
        }
    }
};
