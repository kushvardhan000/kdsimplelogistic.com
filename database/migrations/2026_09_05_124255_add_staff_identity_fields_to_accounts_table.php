<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->string('aadhar_no', 20)->nullable()->after('address');
            $table->string('driving_license_no', 50)->nullable()->after('aadhar_no');
            $table->unique('aadhar_no', 'accounts_aadhar_no_unique');
        });
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropUnique('accounts_aadhar_no_unique');
            $table->dropColumn(['aadhar_no', 'driving_license_no']);
        });
    }
};
