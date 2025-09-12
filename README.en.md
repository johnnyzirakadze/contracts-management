[ქართული](README.md) | [English](README.en.md)

# Contracts Management API – Technical Assignment

This is a Laravel-based Contracts Management API: authentication/JWT, roles (RBAC), notifications (Email/SMS), backup, and Swagger documentation.

## Quick Start

1) Requirements
- PHP 8.2+
- Composer
- PostgreSQL 13+

2) Installation
```bash
# 1) Clone and enter
git clone <repo-url> contracts-management && cd contracts-management

# 2) .env configuration
# See the full example with comments in `.env.example`. Copy and adjust:
cp .env.example .env
# Edit .env → fill DB_* (name/user/password) and required fields (APP_URL, etc.)

# 3) Packages
composer install

# 3.1) Cache directories/permissions (do it upfront)
mkdir -p storage/framework/{cache/data,sessions,views,testing} bootstrap/cache
chmod -R 775 storage bootstrap/cache
php artisan optimize:clear

# 4) Keys
php artisan key:generate --ansi
php artisan jwt:secret --force

# 5) Storage symlink (serve files)
php artisan storage:link

# 6) Migrations and seed data
# If the database doesn't exist, create it (e.g., createdb contracts)
php artisan migrate --seed
```

3) Run
```bash
php artisan serve --host=127.0.0.1 --port=8000
```
API base: `http://127.0.0.1:8000/api`

4) Swagger UI
- Open `http://127.0.0.1:8000/api`
- Authorize → paste Bearer JWT
- Persist token on refresh: `L5_SWAGGER_UI_PERSIST_AUTHORIZATION=true`

5) Authentication
- Admin (seed): email `admin@example.com`, password `admin123`
- To get JWT: `POST /api/auth/login` JSON `{ "email":"admin@example.com", "password":"admin123" }`

After configuring .env, clear config cache and set JWT secret:
```bash
php artisan config:clear
php artisan jwt:secret --yes
```

If you get the error "Please provide a valid cache path", run:
```bash
cd /opt/homebrew/var/www/contracts-management && mkdir -p storage/framework/{cache/data,sessions,views,testing} bootstrap/cache && chmod -R 775 storage bootstrap/cache && php artisan optimize:clear | cat
```

## Notifications (Email/SMS)

Test sends:
```bash
# Simple tests
php artisan notify:test --email=you@example.com
php artisan notify:test --phone=+9955XXXXXXX

# To a specific contract's recipients (responsible/initiator/group), with custom From
php artisan notify:test --contract=1 --days=30 --from=sender@yourdomain.com
```

SMTP: set `MAIL_MAILER=smtp` and fill `MAIL_HOST/PORT/USERNAME/PASSWORD` (for Gmail, use an App Password).
Twilio: fill `TWILIO_SID/TWILIO_TOKEN/TWILIO_FROM`.

## Roles and Access (RBAC)
- Viewer: view/search/export
- Editor: create/edit/upload files
- Approver: approve/change status, view audit log
- Admin: full access, manage users/reference data
- Editing/uploading a record: only the owner (`responsible_manager_id`) or Admin

## Backup
- Daily 02:00 – database only; Weekly (Mon) 03:00 – full
- Retention ≥ 30 days (`config/backup.php`)

Manual run:
```bash
php artisan backup:run --only-db
php artisan backup:run
```
Files stored at: `storage/app/private/ContractsManagement/`.

Cron on server:
```bash
* * * * * php /opt/homebrew/var/www/contracts-management/artisan schedule:run >> /dev/null 2>&1
```

## Security
- Force HTTPS in production (`APP_FORCE_HTTPS=true` also forces locally)
- HSTS header (`Strict-Transport-Security`) in production/force mode
- JWT hardening: token_version/device checks


