<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * دسترسی این کارفرما به ابزار رنگ‌آمیزی — فقط مدیر کل کنترلش می‌کند.
     * true = اعضای این کارفرما می‌توانند نامحدود از ابزار استفاده کنند.
     */
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->boolean('tool_access')->default(true)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('tool_access');
        });
    }
};
