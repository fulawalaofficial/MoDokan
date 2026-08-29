<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('shop_id')->nullable()->index();
                $table->string('name');
                $table->string('email')->unique();
                $table->string('mobile', 20)->nullable();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->string('role', 30)->default('staff');
                $table->string('status', 20)->default('Active');
                $table->json('permissions')->nullable();
                $table->rememberToken();
                $table->timestamps();
            });

            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'shop_id')) {
                $table->unsignedBigInteger('shop_id')->nullable()->index();
            }
            if (!Schema::hasColumn('users', 'mobile')) {
                $table->string('mobile', 20)->nullable()->index();
            }
            if (!Schema::hasColumn('users', 'role')) {
                $table->string('role', 30)->default('staff');
            }
            if (!Schema::hasColumn('users', 'status')) {
                $table->string('status', 20)->default('Active');
            }
            if (!Schema::hasColumn('users', 'permissions')) {
                $table->json('permissions')->nullable();
            }
        });
    }

    public function down(): void
    {
        // Intentionally left non-destructive for an existing production database.
    }
};
