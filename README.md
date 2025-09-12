# [ქართული](README.md) | [English](README.en.md)

#
#
# კონტრაქტების მართვის API – ტექნიკური დავალება

ეს არის Laravel-ზე აგებული კონტრაქტების მართვის API: ავტენტიკაცია/JWT, როლები (RBAC), შეტყობინებები (Email/SMS), ბექაპი და Swagger დოკუმენტაცია.

## სწრაფი გაშვება

1) მოთხოვნები
- PHP 8.2+
- Composer
- PostgreSQL 13+

2) ინსტალაცია
```bash
# 1) კლონი და შესვლა
git clone <repo-url> contracts-management && cd contracts-management

# 2) .env
cp .env.example .env
# დაარედაქტირე .env → შეავსე DB_* (სახელი/მომხმარებელი/პაროლი) და საჭირო ველები (APP_URL და სხვ.)

# 3) პაკეტები
composer install

# 3.1) Cache დირექტორიები/პერმისიები (ბარემ თავიდანვე)
mkdir -p storage/framework/{cache/data,sessions,views,testing} bootstrap/cache
chmod -R 775 storage bootstrap/cache
php artisan optimize:clear

# 4) გასაღებები
php artisan key:generate --ansi
php artisan jwt:secret --force

# 5) Storage symlink (ფაილების სერვინგი)
php artisan storage:link

# 6) მიგრაციები და საწყისი მონაცემები
# თუ ბაზა ჯერ არ არსებობს, შექმენი (მაგ.: createdb contracts)
php artisan migrate --seed
```

3) გაშვება
```bash
php artisan serve --host=127.0.0.1 --port=8000
```
API ბაზა: `http://127.0.0.1:8000/api`

4) Swagger UI
- გახსენი `http://127.0.0.1:8000/api`
- Authorize → ჩასვი Bearer JWT
- ტოკენის შენარჩუნება რეფრეშზე: `L5_SWAGGER_UI_PERSIST_AUTHORIZATION=true`

5) ავტენტიკაცია
- ადმინი (seed): email `admin@example.com`, პაროლი `admin123`
- JWT მისაღებად: `POST /api/auth/login` JSON `{ "email":"admin@example.com", "password":"admin123" }`

## .env ფაილის მაგალითი

```env
APP_NAME="Contracts Management"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost
APP_FORCE_HTTPS=false

# ენა/დროის სარტყელი
APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

# CORS (კომით გამოყოფილი)
ALLOWED_ORIGINS=http://127.0.0.1:8000,http://localhost:8000

# მონაცემთა ბაზა (PostgreSQL)
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=contracts
DB_USERNAME=contracts
DB_PASSWORD=CHANGE_ME

# Queue (ლოკალურად უფრო მარტივად – sync)
QUEUE_CONNECTION=sync

# ელფოსტა (ლოგში ჩაწერა; პროდაქშენზე გადადი smtp-ზე)
MAIL_MAILER=log
MAIL_HOST=
MAIL_PORT=587
MAIL_SCHEME=tls
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=test@example.com
MAIL_FROM_NAME="Contracts"

# Twilio SMS (საჭიროებისამებრ შეავსე)
TWILIO_SID=
TWILIO_TOKEN=
TWILIO_FROM=

# Swagger UI
L5_SWAGGER_UI_PERSIST_AUTHORIZATION=true
L5_SWAGGER_CONST_HOST=http://localhost

# JWT (გაუშვი: php artisan jwt:secret)
JWT_SECRET=

# ფაილების დისკი
FILESYSTEM_DISK=public
```

.env გამართვის შემდეგ ამოშალე ქეში და დააყენე JWT საიდუმლო:
```bash
php artisan config:clear
php artisan jwt:secret --yes
```

თუ მიიღე შეცდომა "Please provide a valid cache path", გაუშვი:
```bash
cd /opt/homebrew/var/www/contracts-management && mkdir -p storage/framework/{cache/data,sessions,views,testing} bootstrap/cache && chmod -R 775 storage bootstrap/cache && php artisan optimize:clear | cat
```

## შეტყობინებები (Email/SMS)

ტესტური გაგზავნები:
```bash
# უბრალო ტესტები
php artisan notify:test --email=you@example.com
php artisan notify:test --phone=+9955XXXXXXX

# კონკრეტული კონტრაქტის მიმღებებზე (პასუხისმგებელი/ინიციატორი/ჯგუფი), მორგებული From-ით
php artisan notify:test --contract=1 --days=30 --from=sender@yourdomain.com
```

SMTP: დააყენე `MAIL_MAILER=smtp` და შეავსე `MAIL_HOST/PORT/USERNAME/PASSWORD` (Gmail-ზე გამოიყენე App Password).
Twilio: შეავსე `TWILIO_SID/TWILIO_TOKEN/TWILIO_FROM`.

## როლები და წვდომები (RBAC)
- Viewer: ნახვა/ძიება/ექსპორტი
- Editor: შექმნა/რედაქტირება/ფაილების ატვირთვა
- Approver: დამტკიცება/სტატუსის ცვლილება, აუდიტ-ლოგის ნახვა
- Admin: სრული წვდომა, მომხმარებლების/საცნობაროების მართვა
- ჩანაწერის რედაქტირება/ატვირთვა: მხოლოდ მფლობელს (`responsible_manager_id`) ან Admin-ს

## ბექაპი
- ყოველდღე 02:00 – მხოლოდ ბაზა; ყოველკვირა (ორშ.) 03:00 – სრული
- შენახვა ≥ 30 დღე (`config/backup.php`)

ხელით გაშვება:
```bash
php artisan backup:run --only-db
php artisan backup:run
```
ფაილები ინახება: `storage/app/private/ContractsManagement/`.

Cron სერვერზე:
```bash
* * * * * php /opt/homebrew/var/www/contracts-management/artisan schedule:run >> /dev/null 2>&1
```

## უსაფრთხოება
- პროდაქშენში HTTPS იძულებით (`APP_FORCE_HTTPS=true` შემთხვევაში ლოკალზეც)
- HSTS ჰედერი (`Strict-Transport-Security`) პროდაქშენში/force რეჟიმში
- JWT დაცვა: token_version/device შემოწმებები