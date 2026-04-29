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
        Schema::create('dealer_order_snapshots', function (Blueprint $table) {
            $table->id();
            $table->string('record_key')->unique();
            $table->string('dealer_scope')->index();
            $table->unsignedBigInteger('dealer_id')->nullable()->index();
            $table->string('dealer_name')->nullable()->index();
            $table->string('dealer_user_name')->nullable();
            $table->string('dealer_user_email')->nullable()->index();
            $table->string('session_source')->nullable();
            $table->string('source_endpoint');
            $table->string('queried_status')->nullable()->index();
            $table->unsignedInteger('queried_page')->nullable();
            $table->unsignedBigInteger('external_order_id')->nullable()->index();
            $table->unsignedBigInteger('container_id')->nullable()->index();
            $table->string('order_number')->nullable()->index();
            $table->string('dealer_reference')->nullable()->index();
            $table->string('customer_name')->nullable()->index();
            $table->string('status')->nullable()->index();
            $table->string('state')->nullable()->index();
            $table->string('payment_status')->nullable()->index();
            $table->string('payment_method')->nullable()->index();
            $table->string('currency', 12)->nullable();
            $table->decimal('total_amount', 12, 2)->nullable();
            $table->decimal('subtotal_amount', 12, 2)->nullable();
            $table->decimal('discount_amount', 12, 2)->nullable();
            $table->decimal('deposit_amount', 12, 2)->nullable();
            $table->decimal('paid_amount', 12, 2)->nullable();
            $table->decimal('balance_amount', 12, 2)->nullable();
            $table->decimal('shipping_amount', 12, 2)->nullable();
            $table->decimal('tax_amount', 12, 2)->nullable();
            $table->unsignedBigInteger('lead_id')->nullable()->index();
            $table->string('lead_reference')->nullable()->index();
            $table->date('order_date')->nullable()->index();
            $table->dateTime('submitted_at_api')->nullable()->index();
            $table->dateTime('created_at_api')->nullable()->index();
            $table->dateTime('updated_at_api')->nullable()->index();
            $table->dateTime('paid_at_api')->nullable()->index();
            $table->dateTime('lead_sent_at')->nullable()->index();
            $table->json('raw_payload');
            $table->timestamp('synced_at')->nullable()->index();
            $table->timestamps();

            $table->index(['dealer_scope', 'status']);
            $table->index(['dealer_scope', 'payment_status']);
            $table->index(['dealer_scope', 'order_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dealer_order_snapshots');
    }
};
