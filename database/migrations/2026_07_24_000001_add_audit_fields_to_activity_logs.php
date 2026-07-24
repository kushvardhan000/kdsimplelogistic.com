<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->string('role')->nullable()->after('user_id');
            $table->string('record_summary')->nullable()->after('record_id');
            $table->string('ip_address')->nullable()->after('record_summary');
            $table->string('user_agent')->nullable()->after('ip_address');
            $table->boolean('success')->default(true)->after('user_agent');
            $table->string('method')->nullable()->after('success');
            $table->string('url')->nullable()->after('method');
            $table->json('old_values')->nullable()->after('url');
            $table->json('new_values')->nullable()->after('old_values');

            $table->index('role');
            $table->index('success');
            $table->index('method');
        });
    }

    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropIndex(['success']);
            $table->dropIndex(['method']);
            $table->dropColumn([
                'role',
                'record_summary',
                'ip_address',
                'user_agent',
                'success',
                'method',
                'url',
                'old_values',
                'new_values',
            ]);
        });
    }
};
