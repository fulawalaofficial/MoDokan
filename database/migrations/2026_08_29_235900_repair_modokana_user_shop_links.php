<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            !Schema::hasTable('users') ||
            !Schema::hasTable('shops')
        ) {
            return;
        }

        if (!Schema::hasColumn(
            'users',
            'shop_id'
        )) {
            Schema::table(
                'users',
                function (Blueprint $table) {
                    $table
                        ->unsignedBigInteger('shop_id')
                        ->nullable();
                }
            );
        }

        /*
         * Repair older owner accounts:
         * shops.owner_id -> users.id
         * users.shop_id  <- shops.id
         */
        $shops = DB::table('shops')
            ->whereNotNull('owner_id')
            ->select(['id', 'owner_id'])
            ->get();

        foreach ($shops as $shop) {
            DB::table('users')
                ->where('id', $shop->owner_id)
                ->where(function ($query) use ($shop) {
                    $query
                        ->whereNull('shop_id')
                        ->orWhere(
                            'shop_id',
                            0
                        );
                })
                ->update([
                    'shop_id' => $shop->id,
                ]);
        }
    }

    public function down(): void
    {
        /*
         * Intentionally empty.
         * This migration repairs account links and should not
         * delete valid user/shop mappings on rollback.
         */
    }
};
