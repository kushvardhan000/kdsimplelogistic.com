<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->string('bank_account_no')->nullable()->after('driving_license_no');
            $table->string('bank_ifsc_code', 11)->nullable()->after('bank_account_no');
            $table->string('bank_name')->nullable()->after('bank_ifsc_code');
        });
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn(['bank_account_no', 'bank_ifsc_code', 'bank_name']);
        });
    }
};
