<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->string('panel', 20)->nullable()->after('ip_address');
            $table->text('user_agent')->nullable()->after('panel');
            $table->string('description', 500)->nullable()->after('user_agent');

            $table->index('panel');
            $table->index('action');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropIndex(['panel']);
            $table->dropIndex(['action']);
            $table->dropIndex(['created_at']);
            $table->dropColumn(['panel', 'user_agent', 'description']);
        });
    }
};
