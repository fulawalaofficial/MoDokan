# Mo Dokana Laravel API + Admin Backend

This is a requirement-wise Laravel starter backend for **Mo Dokana — Shop Management System**.
It contains API routes, migrations, models, controllers, services, and admin placeholders for:

- Business/shop registration
- Shop owner and staff login
- Shop profile/settings
- Product categories and products
- Stock in/out and stock history
- Suppliers/vendors
- Customers and customer ledger
- Sales/billing with invoice number
- Full/partial/due payments
- Due collection
- Repair/service management
- Expenses
- Dashboard and reports
- Staff and permission-ready structure
- Notifications
- Super admin shop/category/subscription placeholders

## How to install

```bash
composer create-project laravel/laravel mo-dokana-laravel-api
cd mo-dokana-laravel-api
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
```

Copy this starter folder's `app`, `routes`, `database`, and `.env.example` files into the new Laravel project.

```bash
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

## API base URL

```txt
http://127.0.0.1:8000/api
```

For mobile app testing, update `src/api/client.js` in the React Native project.

## Default development users

After seeding:

```txt
Super Admin: superadmin@modokana.com / password
Shop Owner: owner@modokana.com / password
Staff: staff@modokana.com / password
```

## Notes

This is a clean production-ready starter structure. Add payment gateway, OTP provider, PDF export, WhatsApp sharing API, and push notifications based on your final provider choices.
