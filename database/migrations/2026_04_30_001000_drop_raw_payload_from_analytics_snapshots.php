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
        Schema::table('dealer_order_snapshots', function (Blueprint $table) {
            $table->dropColumn('raw_payload');
        });

        Schema::table('dealer_lead_snapshots', function (Blueprint $table) {
            $table->dropColumn('raw_payload');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dealer_order_snapshots', function (Blueprint $table) {
            $table->json('raw_payload')->nullable()->after('lead_sent_at');
        });

        Schema::table('dealer_lead_snapshots', function (Blueprint $table) {
            $table->json('raw_payload')->nullable()->after('sent_at_api');
        });
    }
};
