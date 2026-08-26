<?php
namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Shop;
use App\Models\ShopCategory;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $categories = ['Mobile shop'=>true,'Grocery shop'=>false,'Electronics shop'=>true,'Clothes shop'=>false,'Hardware shop'=>true,'Medicine shop'=>false,'Furniture shop'=>false,'Book store'=>false,'Computer shop'=>true,'Bike/car parts shop'=>true,'General store'=>false,'Cosmetic shop'=>false,'Jewelry shop'=>true,'Repair shop'=>true,'Other'=>false];
        foreach ($categories as $name => $repair) {
            ShopCategory::firstOrCreate(['name'=>$name], ['supports_repair'=>$repair, 'status'=>'Active']);
        }

        $mobileCategory = ShopCategory::where('name','Mobile shop')->first();
        $shop = Shop::firstOrCreate(['name'=>'Demo Mobile Dokana'], [
            'shop_category_id'=>$mobileCategory->id, 'address'=>'Bhubaneswar, Odisha', 'contact_number'=>'9999999999', 'currency'=>'INR', 'invoice_prefix'=>'MD', 'low_stock_alert'=>5, 'status'=>'Active'
        ]);

        $super = User::firstOrCreate(['email'=>'superadmin@modokana.com'], ['name'=>'Super Admin','mobile'=>'9000000000','password'=>Hash::make('password'),'role'=>'super_admin','status'=>'Active']);
        $owner = User::firstOrCreate(['email'=>'owner@modokana.com'], ['shop_id'=>$shop->id,'name'=>'Demo Owner','mobile'=>'9111111111','password'=>Hash::make('password'),'role'=>'shop_owner','status'=>'Active']);
        $staff = User::firstOrCreate(['email'=>'staff@modokana.com'], ['shop_id'=>$shop->id,'name'=>'Demo Staff','mobile'=>'9222222222','password'=>Hash::make('password'),'role'=>'staff','status'=>'Active','permissions'=>['sales','customers','stock','repairs']]);
        $shop->update(['owner_id'=>$owner->id]);

        $pc = ProductCategory::firstOrCreate(['shop_id'=>$shop->id,'name'=>'Accessories']);
        $supplier = Supplier::firstOrCreate(['shop_id'=>$shop->id,'name'=>'Demo Supplier'], ['mobile'=>'9333333333','address'=>'Cuttack']);
        Product::firstOrCreate(['shop_id'=>$shop->id,'sku'=>'CHG-001'], ['product_category_id'=>$pc->id,'supplier_id'=>$supplier->id,'name'=>'Fast Charger','purchase_price'=>250,'selling_price'=>399,'quantity'=>20,'unit_type'=>'pcs','opening_stock'=>20,'low_stock_alert'=>5]);
        Product::firstOrCreate(['shop_id'=>$shop->id,'sku'=>'EAR-001'], ['product_category_id'=>$pc->id,'supplier_id'=>$supplier->id,'name'=>'Earphone','purchase_price'=>120,'selling_price'=>249,'quantity'=>30,'unit_type'=>'pcs','opening_stock'=>30,'low_stock_alert'=>5]);
        Customer::firstOrCreate(['shop_id'=>$shop->id,'mobile'=>'9444444444'], ['name'=>'Demo Customer','address'=>'Bhubaneswar']);
    }
}
