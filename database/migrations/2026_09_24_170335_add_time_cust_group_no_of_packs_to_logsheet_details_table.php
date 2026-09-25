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
        Schema::table('logsheet_details', function (Blueprint $table) {
            if (!Schema::hasColumn('logsheet_details', 'time')) {
                $table->string('time')->nullable()->after('difference_placeholder');
            }
            if (!Schema::hasColumn('logsheet_details', 'cust_group')) {
                $table->string('cust_group')->nullable()->after('time');
            }
            if (!Schema::hasColumn('logsheet_details', 'no_of_packs')) {
                $table->unsignedInteger('no_of_packs')->nullable()->after('cust_group');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('logsheet_details', function (Blueprint $table) {
            $columnsToDrop = [];
            if (Schema::hasColumn('logsheet_details', 'time')) {
                $columnsToDrop[] = 'time';
            }
            if (Schema::hasColumn('logsheet_details', 'cust_group')) {
                $columnsToDrop[] = 'cust_group';
            }
            if (Schema::hasColumn('logsheet_details', 'no_of_packs')) {
                $columnsToDrop[] = 'no_of_packs';
            }
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
