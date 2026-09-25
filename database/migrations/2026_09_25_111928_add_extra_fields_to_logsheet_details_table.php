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
            if (!Schema::hasColumn('logsheet_details', 'extra_fields')) {
                $table->json('extra_fields')->nullable()->after('no_of_packs');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('logsheet_details', function (Blueprint $table) {
            if (Schema::hasColumn('logsheet_details', 'extra_fields')) {
                $table->dropColumn('extra_fields');
            }
        });
    }
};