<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('feed_syncs', function (Blueprint $t): void {
            $t->uuid('lease_token')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('feed_syncs', function (Blueprint $t): void {
            $t->dropColumn('lease_token');
        });
    }
};
