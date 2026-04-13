<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplies', function (Blueprint $table) {
            $table->string('module', 20)->default('shared')->after('supply_category_id');
            $table->index('module');
        });
    }

    public function down(): void
    {
        Schema::table('supplies', function (Blueprint $table) {
            $table->dropIndex(['module']);
            $table->dropColumn('module');
        });
    }
};
