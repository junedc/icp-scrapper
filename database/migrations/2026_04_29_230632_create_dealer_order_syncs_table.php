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
        Schema::create('dealer_order_syncs', function (Blueprint $table) {
            $table->id();
            $table->string('dealer_scope')->index();
            $table->unsignedInteger('dealer_id')->nullable();
            $table->string('dealer_name')->nullable();
            $table->string('dealer_user_email')->nullable();
            $table->string('session_source')->nullable();
            $table->string('status')->default('queued')->index();
            $table->string('current_status')->nullable();
            $table->unsignedInteger('current_page')->nullable();
            $table->unsignedInteger('last_page')->nullable();
            $table->unsignedInteger('total_records')->default(0);
            $table->unsignedInteger('delay_ms')->default(350);
            $table->boolean('create_only')->default(true);
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dealer_order_syncs');
    }
};
