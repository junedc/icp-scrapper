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
        Schema::create('dealer_lead_snapshots', function (Blueprint $table) {
            $table->id();
            $table->string('record_key')->unique();
            $table->string('dealer_scope')->index();
            $table->unsignedBigInteger('dealer_id')->nullable()->index();
            $table->string('dealer_name')->nullable()->index();
            $table->string('dealer_user_name')->nullable();
            $table->string('dealer_user_email')->nullable()->index();
            $table->string('session_source')->nullable();
            $table->string('source_endpoint');
            $table->unsignedInteger('queried_page')->nullable();
            $table->unsignedBigInteger('external_lead_id')->nullable()->index();
            $table->unsignedBigInteger('container_id')->nullable()->index();
            $table->string('lead_reference')->nullable()->index();
            $table->string('status')->nullable()->index();
            $table->string('state')->nullable()->index();
            $table->string('currency', 12)->nullable();
            $table->decimal('amount', 12, 2)->nullable();
            $table->decimal('quoted_amount', 12, 2)->nullable();
            $table->decimal('order_amount', 12, 2)->nullable();
            $table->date('lead_date')->nullable()->index();
            $table->dateTime('expiry_at')->nullable()->index();
            $table->dateTime('created_at_api')->nullable()->index();
            $table->dateTime('updated_at_api')->nullable()->index();
            $table->dateTime('sent_at_api')->nullable()->index();
            $table->json('raw_payload');
            $table->timestamp('synced_at')->nullable()->index();
            $table->timestamps();

            $table->index(['dealer_scope', 'status']);
            $table->index(['dealer_scope', 'lead_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dealer_lead_snapshots');
    }
};
