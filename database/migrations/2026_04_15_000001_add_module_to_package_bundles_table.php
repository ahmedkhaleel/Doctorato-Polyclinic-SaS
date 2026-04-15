<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('package_bundles', function (Blueprint $table) {
            $table->string('module', 50)->default('derma')->after('display_order');
        });
    }

    public function down(): void
    {
        Schema::table('package_bundles', function (Blueprint $table) {
            $table->dropColumn('module');
        });
    }
};
