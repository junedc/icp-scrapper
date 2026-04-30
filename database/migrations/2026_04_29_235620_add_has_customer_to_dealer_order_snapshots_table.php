<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('dealer_order_snapshots', function (Blueprint $table) {
            $table->boolean('has_customer')->default(false)->after('customer_name');
        });

        DB::table('dealer_order_snapshots')
            ->whereNotNull('customer_name')
            ->where('customer_name', '!=', '')
            ->update(['has_customer' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dealer_order_snapshots', function (Blueprint $table) {
            $table->dropColumn('has_customer');
        });
    }
};
