<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('shop_categories')) {
            Schema::create('shop_categories', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->text('description')->nullable();
                $table->boolean('supports_repair')->default(false);
                $table->string('status', 20)->default('Active');
                $table->timestamps();
            });

            return;
        }

        Schema::table('shop_categories', function (Blueprint $table) {
            if (!Schema::hasColumn('shop_categories', 'description')) {
                $table->text('description')->nullable();
            }
            if (!Schema::hasColumn('shop_categories', 'supports_repair')) {
                $table->boolean('supports_repair')->default(false);
            }
            if (!Schema::hasColumn('shop_categories', 'status')) {
                $table->string('status', 20)->default('Active');
            }
        });
    }

    public function down(): void
    {
        // Intentionally left non-destructive for an existing production database.
    }
};
