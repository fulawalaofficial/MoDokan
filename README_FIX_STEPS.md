# Mo Dokana register-business server error fix

This package fixes the most common registration failures:

- Missing `shop_categories` table/columns.
- Missing `shops` table/columns.
- Missing `users.shop_id`, `mobile`, `role`, `status`, or `permissions`.
- Missing Laravel Sanctum `personal_access_tokens` table.
- Registration creating a Shop and then failing while creating User.
- Duplicate email/mobile causing SQL errors.
- `shop.active` middleware not being registered.
- Generic 500 response with no useful logging.

## 1. Copy the files

Copy the files from this ZIP into the matching Laravel project paths.

Do NOT overwrite your full `bootstrap/app.php` with the text file included here.
Use `bootstrap/app.php.example-snippet.txt` only to add the `shop.active` middleware alias.

## 2. Install Sanctum if not installed

```bash
composer require laravel/sanctum
```

Laravel 11/12 normally discovers Sanctum automatically.

## 3. Run migrations

```bash
php artisan optimize:clear
php artisan migrate
```

On production:

```bash
php artisan optimize:clear
php artisan migrate --force
```

## 4. Add `.env` setting

If a new shop should be active immediately:

```env
ADMIN_APPROVAL_ENABLED=false
```

If an administrator must approve new shops:

```env
ADMIN_APPROVAL_ENABLED=true
```

Then run:

```bash
php artisan config:clear
php artisan cache:clear
```

## 5. Check routes

```bash
php artisan route:list --path=api
```

You should see:

- POST api/register-business
- POST api/login
- GET api/me

## 6. Test registration

POST `/api/register-business`

JSON example:

```json
{
  "owner_name": "Soumya Ranjan",
  "mobile": "9876543210",
  "email": "owner@example.com",
  "password": "12345678",
  "shop_name": "Mo Dokana Test Shop",
  "shop_category_name": "Mobile Shop",
  "shop_address": "Kujanga, Jagatsinghpur, Odisha",
  "currency": "INR",
  "invoice_prefix": "INV",
  "opening_balance": 0
}
```

Expected status: `201`.

## 7. If it still returns 500

Run:

```bash
tail -n 100 storage/logs/laravel.log
```

or on Windows/local:

```bash
php artisan pail
```

Also verify database connection:

```bash
php artisan tinker
```

Then:

```php
DB::connection()->getPdo();
```

## Important note about `config/auth.php`

Your `config/auth.php` with only the `web` session guard is normal for Laravel Sanctum.
`auth:sanctum` does NOT require adding a separate `sanctum` guard to `config/auth.php`.

## Production permissions

If deployed on Linux hosting:

```bash
chmod -R 775 storage bootstrap/cache
php artisan storage:link
php artisan optimize:clear
```
