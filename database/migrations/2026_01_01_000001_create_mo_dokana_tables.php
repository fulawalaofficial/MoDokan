<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Shop categories
        |--------------------------------------------------------------------------
        */
        Schema::create('shop_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->boolean('supports_repair')->default(false);
            $table->string('status')->default('Active');
            $table->timestamps();
        });

        /*
        |--------------------------------------------------------------------------
        | Shops
        |--------------------------------------------------------------------------
        */
        Schema::create('shops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_category_id')
                ->nullable()
                ->constrained('shop_categories')
                ->nullOnDelete();

            // Kept as a plain ID to avoid a circular foreign key between shops/users.
            $table->unsignedBigInteger('owner_id')->nullable();

            $table->string('name');
            $table->text('address')->nullable();
            $table->string('contact_number')->nullable();
            $table->string('gst_number')->nullable();
            $table->string('logo')->nullable();

            $table->decimal('opening_balance', 12, 2)->default(0);
            $table->string('currency', 10)->default('INR');
            $table->string('invoice_prefix', 20)->default('INV');
            $table->decimal('default_tax', 8, 2)->default(0);
            $table->decimal('low_stock_alert', 12, 2)->default(5);

            $table->string('status')->default('Active');
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        /*
        |--------------------------------------------------------------------------
        | Users
        |--------------------------------------------------------------------------
        | This migration is self-contained:
        | - If the Laravel users table does not exist, it creates it.
        | - If it already exists, it only adds MoDokan-specific fields.
        */
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->foreignId('shop_id')
                    ->nullable()
                    ->constrained('shops')
                    ->nullOnDelete();

                $table->string('name');
                $table->string('email')->unique();
                $table->string('mobile')->nullable();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->string('role')->default('shop_owner');
                $table->string('status')->default('Active');
                $table->json('permissions')->nullable();
                $table->rememberToken();
                $table->timestamps();
            });
        } else {
            if (!Schema::hasColumn('users', 'shop_id')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->foreignId('shop_id')
                        ->nullable()
                        ->constrained('shops')
                        ->nullOnDelete();
                });
            }

            if (!Schema::hasColumn('users', 'mobile')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->string('mobile')->nullable();
                });
            }

            if (!Schema::hasColumn('users', 'role')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->string('role')->default('shop_owner');
                });
            }

            if (!Schema::hasColumn('users', 'status')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->string('status')->default('Active');
                });
            }

            if (!Schema::hasColumn('users', 'permissions')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->json('permissions')->nullable();
                });
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Product categories
        |--------------------------------------------------------------------------
        */
        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status')->default('Active');
            $table->timestamps();
        });

        /*
        |--------------------------------------------------------------------------
        | Suppliers
        |--------------------------------------------------------------------------
        */
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('mobile')->nullable();
            $table->text('address')->nullable();
            $table->string('product_supplied')->nullable();
            $table->decimal('total_purchase', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('due_amount', 12, 2)->default(0);
            $table->string('status')->default('Active');
            $table->timestamps();
        });

        /*
        |--------------------------------------------------------------------------
        | Products
        |--------------------------------------------------------------------------
        */
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_category_id')
                ->nullable()
                ->constrained('product_categories')
                ->nullOnDelete();
            $table->foreignId('supplier_id')
                ->nullable()
                ->constrained('suppliers')
                ->nullOnDelete();

            $table->string('name');
            $table->string('sku')->nullable();
            $table->string('barcode')->nullable();
            $table->string('image')->nullable();
            $table->decimal('purchase_price', 12, 2)->default(0);
            $table->decimal('selling_price', 12, 2)->default(0);
            $table->decimal('quantity', 12, 2)->default(0);
            $table->string('unit_type')->default('pcs');
            $table->decimal('opening_stock', 12, 2)->default(0);
            $table->decimal('low_stock_alert', 12, 2)->default(5);
            $table->string('status')->default('Active');
            $table->timestamps();
        });

        /*
        |--------------------------------------------------------------------------
        | Stock histories
        |--------------------------------------------------------------------------
        */
        Schema::create('stock_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('type');
            $table->decimal('quantity', 12, 2);
            $table->decimal('before_quantity', 12, 2);
            $table->decimal('after_quantity', 12, 2);
            $table->text('note')->nullable();
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->timestamps();
        });

        /*
        |--------------------------------------------------------------------------
        | Customers
        |--------------------------------------------------------------------------
        */
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('mobile')->nullable();
            $table->text('address')->nullable();
            $table->string('photo')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('total_purchase', 12, 2)->default(0);
            $table->decimal('total_paid', 12, 2)->default(0);
            $table->decimal('total_due', 12, 2)->default(0);
            $table->date('last_payment_date')->nullable();
            $table->string('status')->default('Active');
            $table->timestamps();
        });

        /*
        |--------------------------------------------------------------------------
        | Sales
        |--------------------------------------------------------------------------
        */
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('invoice_no')->unique();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('due_amount', 12, 2)->default(0);
            $table->string('payment_method')->nullable();
            $table->string('payment_status')->default('Due');
            $table->string('sale_status')->default('Completed');
            $table->timestamp('sale_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        /*
        |--------------------------------------------------------------------------
        | Sale items
        |--------------------------------------------------------------------------
        */
        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();

            $table->string('product_name');
            $table->decimal('quantity', 12, 2);
            $table->decimal('purchase_price', 12, 2);
            $table->decimal('selling_price', 12, 2);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->decimal('profit', 12, 2)->default(0);
            $table->timestamps();
        });

        /*
        |--------------------------------------------------------------------------
        | Repairs
        |--------------------------------------------------------------------------
        | Created before due_payments so repair_id can have a real FK.
        */
        Schema::create('repairs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('repair_no')->unique();
            $table->string('item_name');
            $table->string('brand_model')->nullable();
            $table->text('problem_description');
            $table->text('item_condition')->nullable();
            $table->date('received_date')->nullable();
            $table->date('expected_return_date')->nullable();
            $table->decimal('estimated_amount', 12, 2)->default(0);
            $table->decimal('advance_amount', 12, 2)->default(0);
            $table->decimal('remaining_amount', 12, 2)->default(0);
            $table->text('parts_used')->nullable();
            $table->decimal('service_charge', 12, 2)->default(0);
            $table->string('status')->default('Received');
            $table->string('delivery_status')->default('Pending');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        /*
        |--------------------------------------------------------------------------
        | Payments
        |--------------------------------------------------------------------------
        */
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->nullableMorphs('payable');
            $table->string('payment_no')->unique();
            $table->decimal('amount', 12, 2);
            $table->string('method')->default('cash');
            $table->string('type')->default('sale_payment');
            $table->text('note')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        /*
        |--------------------------------------------------------------------------
        | Due payments
        |--------------------------------------------------------------------------
        */
        Schema::create('due_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('sale_id')->nullable()->constrained('sales')->nullOnDelete();
            $table->foreignId('repair_id')->nullable()->constrained('repairs')->nullOnDelete();

            $table->decimal('amount', 12, 2);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('remaining_amount', 12, 2)->default(0);
            $table->date('reminder_date')->nullable();
            $table->string('status')->default('Due');
            $table->text('note')->nullable();
            $table->timestamps();
        });

        /*
        |--------------------------------------------------------------------------
        | Expenses
        |--------------------------------------------------------------------------
        */
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('category');
            $table->string('title');
            $table->decimal('amount', 12, 2);
            $table->date('expense_date');
            $table->string('payment_method')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });

        /*
        |--------------------------------------------------------------------------
        | Notifications
        |--------------------------------------------------------------------------
        */
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->nullable()->constrained('shops')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('message');
            $table->string('type')->default('info');
            $table->timestamp('read_at')->nullable();
            $table->json('data')->nullable();
            $table->timestamps();
        });

        /*
        |--------------------------------------------------------------------------
        | Subscription plans
        |--------------------------------------------------------------------------
        */
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('price', 12, 2)->default(0);
            $table->integer('duration_days')->default(30);
            $table->integer('max_staff')->default(1);
            $table->integer('max_products')->default(100);
            $table->json('features')->nullable();
            $table->string('status')->default('Active');
            $table->timestamps();
        });

        /*
        |--------------------------------------------------------------------------
        | Shop subscriptions
        |--------------------------------------------------------------------------
        */
        Schema::create('shop_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops')->cascadeOnDelete();
            $table->foreignId('subscription_plan_id')
                ->nullable()
                ->constrained('subscription_plans')
                ->nullOnDelete();

            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('payment_status')->default('Pending');
            $table->string('status')->default('Active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // Reverse dependency order.
        Schema::dropIfExists('shop_subscriptions');
        Schema::dropIfExists('subscription_plans');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('due_payments');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('repairs');
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('stock_histories');
        Schema::dropIfExists('products');
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('product_categories');

        /*
         * This migration may have created users itself.
         * In this MoDokan package, users is expected to be owned by this migration.
         */
        Schema::dropIfExists('users');

        Schema::dropIfExists('shops');
        Schema::dropIfExists('shop_categories');
    }
};
