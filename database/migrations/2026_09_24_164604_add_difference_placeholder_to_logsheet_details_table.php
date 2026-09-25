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
            if (!Schema::hasColumn('logsheet_details', 'difference_placeholder')) {
                $table->decimal('difference_placeholder', 14, 3)->nullable()
                    ->after('difference');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('logsheet_details', function (Blueprint $table) {
            if (Schema::hasColumn('logsheet_details', 'difference_placeholder')) {
                $table->dropColumn('difference_placeholder');
            }
        });
    }
};
