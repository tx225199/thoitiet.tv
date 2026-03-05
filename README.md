# Laravel Project – XOSOVN.NET

Dự án Laravel này sử dụng Laravel Framework phiên bản 10.x và PHP >= 8.1. Đây là một ứng dụng web với nhiều chức năng xử lý dữ liệu nền (queue), crawl dữ liệu từ web, resize ảnh, API authentication và giao diện quản lý log.

---

## Yêu cầu hệ thống

- PHP >= 8.1
- Composer
- MySQL
- Node.js (Sử dụng queue nền qua PM2)
- Các PHP extensions bắt buộc:
  - `fileinfo`
  - `mbstring`
  - `pcntl`
  - `pdo`
  - `pdo_mysql` (hoặc `pdo_pgsql`)
  - `gd`
- Set quyền ghi cho các thư mục:
  - `storage/`
  - `bootstrap/cache`

---

## Cài đặt

```bash
# Clone source code
git clone <repository_url> project-name
cd project-name

# Cài composer packages
composer install

# Tạo file .env từ file mẫu
cp .env.example .env

# Cấu hình các thông số trong .env:
# - DB_DATABASE, DB_USERNAME, DB_PASSWORD
# - APP_URL
# - QUEUE_CONNECTION=database

# Generate APP_KEY
php artisan key:generate

# Tạo symbolic link cho thư mục storage (phục vụ ảnh, file upload)
php artisan storage:link

# Cấp quyền ghi cho storage và bootstrap/cache
chmod -R 777 storage
chmod -R 777 bootstrap/cache

# Chạy migrate database
php artisan migrate
```

---

## CI/CD – Các bước cần chạy khi deploy

```bash
# 1. Pull code mới nhất từ nhánh chính
git pull origin main

# 2. Cài đặt composer (không cài dev dependencies)
composer install --no-dev --optimize-autoloader

# 3. Dọn sạch cache cũ
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# 4. Chạy migrate database
php artisan migrate --force

# 5. Cache lại toàn bộ config và routes
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. Cấp quyền lại cho storage và bootstrap/cache
chmod -R 775 storage
chmod -R 775 bootstrap/cache

# 7. Restart queue và workers
php artisan queue:restart
pm2 restart all
```

---

## Cronjob Laravel Scheduler

Thêm dòng sau vào crontab để kích hoạt scheduler Laravel:

```bash
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

Lưu ý:
- `/path/to/project` là đường dẫn tuyệt đối đến thư mục Laravel
- Laravel sẽ tự kiểm tra thời điểm chạy các lệnh định kỳ trong `app/Console/Kernel.php`

---

## ⚡ Cấu hình queue với PM2

Laravel sử dụng `pm2` để chạy queue nền:

```bash
pm2 start /path/to/project/ecosystem.config.cjs
```

- `/path/to/project` là thư mục chứa code Laravel
- File `ecosystem.config.cjs` chứa định nghĩa các queue: `default`, v.v...

---

## Cấu hình upload lớn trong Nginx & PHP-FPM

### 1. Cập nhật Nginx

**Nếu dùng block `server` hoặc `http`:**

```nginx
client_max_body_size 100M;
```

```bash
sudo nginx -t
sudo systemctl reload nginx
```

---

### 2. Cập nhật PHP-FPM (PHP 8.3)

```bash
sudo nano /etc/php/8.3/fpm/php.ini
```

Chỉnh các thông số:

```ini
upload_max_filesize = 100M
post_max_size = 100M
max_execution_time = 300
max_input_time = 300
```

Khởi động lại PHP-FPM:

```bash
sudo systemctl restart php8.3-fpm
```

---

## Các package chính sử dụng

- `fabpot/goutte` – HTML crawler
- `guzzlehttp/guzzle` – HTTP client
- `intervention/image` – Xử lý ảnh
- `laravel/sanctum` – API authentication
- `laravel/ui` – Giao diện đăng nhập Bootstrap
- `opcodesio/log-viewer` – Giao diện xem log Laravel
- `barryvdh/laravel-debugbar` – Debug SQL, request, view
- `jenssegers/agent` – Phân loại thiết bị người dùng

---

## Ghi chú

- Nhánh chính của project: `main`
- Đảm bảo:
  - Đã chạy `php artisan queue:restart` sau khi deploy
  - Đã cập nhật `nginx.conf` và `php.ini` nếu upload file lớn
  - Đừng quên:
    - `php artisan queue:restart` sau khi deploy
    - `pm2 start /path/to/project/queue_worker.yml` sau khi boot server hoặc thay đổi file cấu hình queue
    - `pm2 save` để tự động start pm2 khi restart server
- Đảm bảo cronjob `schedule:run` được cấu hình vì có sử dụng Laravel scheduler