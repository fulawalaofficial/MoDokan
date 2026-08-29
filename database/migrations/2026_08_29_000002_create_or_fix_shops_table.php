<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('shops')) {
            Schema::create('shops', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('shop_category_id')->nullable()->index();
                $table->unsignedBigInteger('owner_id')->nullable()->index();
                $table->string('name');
                $table->text('address');
                $table->string('contact_number', 20);
                $table->string('gst_number', 50)->nullable();
                $table->string('logo')->nullable();
                $table->decimal('opening_balance', 15, 2)->default(0);
                $table->string('currency', 10)->default('INR');
                $table->string('invoice_prefix', 20)->default('INV');
                $table->decimal('default_tax', 8, 2)->default(0);
                $table->unsignedInteger('low_stock_alert')->default(5);
                $table->string('status', 20)->default('Active');
                $table->json('settings')->nullable();
                $table->timestamps();
            });

            return;
        }

        Schema::table('shops', function (Blueprint $table) {
            if (!Schema::hasColumn('shops', 'shop_category_id')) {
                $table->unsignedBigInteger('shop_category_id')->nullable()->index();
            }
            if (!Schema::hasColumn('shops', 'owner_id')) {
                $table->unsignedBigInteger('owner_id')->nullable()->index();
            }
            if (!Schema::hasColumn('shops', 'address')) {
                $table->text('address')->nullable();
            }
            if (!Schema::hasColumn('shops', 'contact_number')) {
                $table->string('contact_number', 20)->nullable();
            }
            if (!Schema::hasColumn('shops', 'gst_number')) {
                $table->string('gst_number', 50)->nullable();
            }
            if (!Schema::hasColumn('shops', 'logo')) {
                $table->string('logo')->nullable();
            }
            if (!Schema::hasColumn('shops', 'opening_balance')) {
                $table->decimal('opening_balance', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('shops', 'currency')) {
                $table->string('currency', 10)->default('INR');
            }
            if (!Schema::hasColumn('shops', 'invoice_prefix')) {
                $table->string('invoice_prefix', 20)->default('INV');
            }
            if (!Schema::hasColumn('shops', 'default_tax')) {
                $table->decimal('default_tax', 8, 2)->default(0);
            }
            if (!Schema::hasColumn('shops', 'low_stock_alert')) {
                $table->unsignedInteger('low_stock_alert')->default(5);
            }
            if (!Schema::hasColumn('shops', 'status')) {
                $table->string('status', 20)->default('Active');
            }
            if (!Schema::hasColumn('shops', 'settings')) {
                $table->json('settings')->nullable();
            }
        });
    }

    public function down(): void
    {
        // Intentionally left non-destructive for an existing production database.
    }
};
