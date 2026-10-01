# Deployment Strategy: Laravel + Filament + Next.js

**Constraint:** Non-destruktif migration, zero downtime, backward compatible dengan legacy system.
**Update:** Tambah Docker/containerization, staging environment, database replication, SSL, DNS, DR plan, scaling, cost estimation, runbook, testing plan.

---

## 1. Architecture Overview

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                           Production Architecture                            │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐                     │
│  │   CDN       │    │   CDN       │    │   CDN       │                     │
│  │  (Cloudflare)│   │  (Cloudflare)│   │  (Cloudflare)│                    │
│  └──────┬──────┘    └──────┬──────┘    └──────┬──────┘                     │
│         │                  │                  │                             │
│         ▼                  ▼                  ▼                             │
│  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐                     │
│  │  Next.js    │    │  Laravel    │    │  Filament   │                     │
│  │  Frontend   │    │  API        │    │  Admin      │                     │
│  │  (Vercel)   │    │  (Docker)   │    │  (Docker)   │                     │
│  └──────┬──────┘    └──────┬──────┘    └──────┬──────┘                     │
│         │                  │                  │                             │
│         │                  ▼                  │                             │
│         │           ┌─────────────┐           │                             │
│         │           │   Redis     │           │                             │
│         │           │  (Docker)   │           │                             │
│         │           └──────┬──────┘           │                             │
│         │                  │                  │                             │
│         ▼                  ▼                  ▼                             │
│  ┌─────────────────────────────────────────────────────────────┐           │
│  │                      Database Layer                          │           │
│  │  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐      │           │
│  │  │  Legacy DB  │    │  New DB     │    │  ERP DB     │      │           │
│  │  │  (s3Prod)   │    │  (s3_erp)   │    │  (PostgreSQL)│     │           │
│  │  │  MySQL      │    │  MySQL      │    │             │      │           │
│  │  └─────────────┘    └─────────────┘    └─────────────┘      │           │
│  └─────────────────────────────────────────────────────────────┘           │
│                                                                              │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## 2. Infrastructure Setup

### 2.1 Server Requirements

| Component | Specification | Purpose |
|---|---|---|
| **API Server** | 2 vCPU, 4GB RAM, 50GB SSD | Laravel API + Filament (Docker) |
| **Database Server** | 4 vCPU, 8GB RAM, 100GB SSD | MySQL (new) + Redis |
| **Legacy Database** | Existing | s3Prod (read-only) |
| **ERP Database** | Existing | PostgreSQL |
| **CDN** | Cloudflare | Static assets + DDoS protection |
| **Object Storage** | S3-compatible | File uploads |

### 2.2 Network Configuration

```
┌─────────────────────────────────────────────────────────────────┐
│                      Network Architecture                        │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  Internet → Cloudflare CDN → Load Balancer → Servers            │
│                                                                  │
│  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐         │
│  │  Public     │    │  Private    │    │  Database   │         │
│  │  Subnet     │    │  Subnet     │    │  Subnet     │         │
│  │             │    │             │    │             │         │
│  │  Next.js    │    │  Laravel    │    │  MySQL      │         │
│  │  (Vercel)   │    │  API        │    │  Redis      │         │
│  │             │    │  Filament   │    │             │         │
│  └─────────────┘    └─────────────┘    └─────────────┘         │
│                                                                  │
│  Firewall Rules:                                                 │
│  - Public: 80, 443 only                                          │
│  - Private: 8000 (API), 8001 (Filament)                          │
│  - Database: 3306 (MySQL), 6379 (Redis) - internal only         │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

---

## 3. Docker/Containerization

### 3.1 Dockerfile (Laravel API + Filament)

```dockerfile
# Dockerfile

FROM php:8.2-fpm-alpine

# Install dependencies
RUN apk add --no-cache \
    nginx \
    supervisor \
    libpng-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql zip pcntl

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/s3-system

# Copy application
COPY . .

# Install dependencies
RUN composer install --no-dev --optimize-autoloader

# Set permissions
RUN chown -R www-data:www-data /var/www/s3-system/storage /var/www/s3-system/bootstrap/cache

# Copy nginx config
COPY docker/nginx.conf /etc/nginx/nginx.conf

# Copy supervisor config
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Expose port
EXPOSE 80

# Start supervisor
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
```

### 3.2 Docker Compose

```yaml
# docker-compose.yml

version: '3.8'

services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    container_name: s3-app
    restart: unless-stopped
    ports:
      - "8000:80"
    volumes:
      - .:/var/www/s3-system
      - ./storage:/var/www/s3-system/storage
    environment:
      - APP_ENV=production
      - DB_HOST=db
      - DB_DATABASE=s3_erp
      - DB_USERNAME=s3_app
      - DB_PASSWORD=SecurePassword123!
      - REDIS_HOST=redis
    depends_on:
      - db
      - redis
    networks:
      - s3-network

  db:
    image: mysql:8.0
    container_name: s3-db
    restart: unless-stopped
    ports:
      - "3306:3306"
    environment:
      - MYSQL_ROOT_PASSWORD=rootpassword
      - MYSQL_DATABASE=s3_erp
      - MYSQL_USER=s3_app
      - MYSQL_PASSWORD=SecurePassword123!
    volumes:
      - db-data:/var/lib/mysql
    networks:
      - s3-network

  redis:
    image: redis:7-alpine
    container_name: s3-redis
    restart: unless-stopped
    ports:
      - "6379:6379"
    volumes:
      - redis-data:/data
    networks:
      - s3-network

  queue:
    build:
      context: .
      dockerfile: Dockerfile
    container_name: s3-queue
    restart: unless-stopped
    command: php artisan queue:work redis --sleep=3 --tries=3
    volumes:
      - .:/var/www/s3-system
    environment:
      - APP_ENV=production
      - DB_HOST=db
      - REDIS_HOST=redis
    depends_on:
      - db
      - redis
    networks:
      - s3-network

  scheduler:
    build:
      context: .
      dockerfile: Dockerfile
    container_name: s3-scheduler
    restart: unless-stopped
    command: php artisan schedule:work
    volumes:
      - .:/var/www/s3-system
    environment:
      - APP_ENV=production
      - DB_HOST=db
      - REDIS_HOST=redis
    depends_on:
      - db
      - redis
    networks:
      - s3-network

volumes:
  db-data:
  redis-data:

networks:
  s3-network:
    driver: bridge
```

### 3.3 Docker Commands

```bash
# Build and start containers
docker-compose up -d --build

# View logs
docker-compose logs -f app

# Run migrations
docker-compose exec app php artisan migrate --force

# Run seeders
docker-compose exec app php artisan db:seed

# Cache config
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache
docker-compose exec app php artisan view:cache

# Restart containers
docker-compose restart

# Stop containers
docker-compose down
```

---

## 4. Environment Configuration

### 4.1 Laravel .env

```env
APP_NAME="S3 System"
APP_ENV=production
APP_KEY=base64:xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
APP_DEBUG=false
APP_URL=https://api.find-service.co.id

# Legacy Database (read-only)
DB_CONNECTION=mysql
DB_HOST=172.16.1.2
DB_PORT=3306
DB_DATABASE=s3Prod
DB_USERNAME=admins
DB_PASSWORD=fid123!!

# New Database
DB_CONNECTION_NEW=mysql
DB_HOST_NEW=127.0.0.1
DB_PORT_NEW=3306
DB_DATABASE_NEW=s3_erp
DB_USERNAME_NEW=s3_app
DB_PASSWORD_NEW=SecurePassword123!

# ERP Database
DB_CONNECTION_PG=pgsql
DB_HOST_PG=172.16.1.2
DB_PORT_PG=5432
DB_DATABASE_PG=erp_data
DB_USERNAME_PG=erp_user
DB_PASSWORD_PG=erp_pass

# Cache & Session
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Sanctum
SANCTUM_STATEFUL_DOMAINS=app.find-service.co.id,find-service.co.id

# CORS
FRONTEND_URL=https://app.find-service.co.id

# Filament
FILAMENT_PATH=admin
FILAMENT_DOMAIN=find-service.co.id

# Mail
MAIL_MAILER=smtp
MAIL_HOST=203.0.113.15
MAIL_PORT=25
MAIL_USERNAME=calcenter.fid@find-service.co.id
MAIL_PASSWORD=fid123!!
MAIL_FROM_ADDRESS=calcenter.fid@find-service.co.id
MAIL_FROM_NAME="S3 System"

# Logging
LOG_CHANNEL=stack
LOG_LEVEL=warning

# Feature Flags
FEATURE_NEW_TICKET=false
FEATURE_NEW_LOAN=false
FEATURE_NEW_DASHBOARD=false
FEATURE_DUAL_WRITE=false

# WebSocket
REVERB_APP_ID=your-app-id
REVERB_APP_KEY=your-app-key
REVERB_APP_SECRET=your-app-secret
REVERB_HOST=localhost
REVERB_PORT=8080

# Webhook
WEBHOOK_SECRET=your-webhook-secret
```

### 4.2 Next.js .env

```env
# .env.production
NEXT_PUBLIC_API_URL=https://api.find-service.co.id/api/v1
NEXT_PUBLIC_APP_NAME=S3 System
NEXT_PUBLIC_WS_URL=wss://api.find-service.co.id
```

---

## 5. SSL Certificate Management

### 5.1 Let's Encrypt with Certbot

```bash
# Install certbot
sudo apt install certbot python3-certbot-nginx -y

# Obtain certificate
sudo certbot --nginx -d api.find-service.co.id -d app.find-service.co.id -d find-service.co.id

# Auto-renewal
sudo certbot renew --dry-run

# Cron job for auto-renewal
echo "0 0 * * * certbot renew --quiet" | sudo crontab -
```

### 5.2 Cloudflare SSL

```
SSL/TLS Overview:
- Encryption mode: Full (strict)
- Always Use HTTPS: ON
- Automatic HTTPS Rewrites: ON
- Minimum TLS Version: 1.2
```

---

## 6. Domain/DNS Configuration

### 6.1 DNS Records

| Type | Name | Value | TTL |
|---|---|---|---|
| A | find-service.co.id | 203.0.113.122 | 300 |
| A | api.find-service.co.id | 203.0.113.122 | 300 |
| A | app.find-service.co.id | 203.0.113.122 | 300 |
| CNAME | www | find-service.co.id | 300 |

### 6.2 Nginx Configuration

```nginx
# /etc/nginx/sites-available/find-service.co.id

server {
    listen 80;
    server_name find-service.co.id api.find-service.co.id app.find-service.co.id;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name api.find-service.co.id;

    ssl_certificate /etc/letsencrypt/live/find-service.co.id/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/find-service.co.id/privkey.pem;

    root /var/www/s3-system/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

---

## 7. Email Configuration

### 7.1 SMTP Configuration

```env
MAIL_MAILER=smtp
MAIL_HOST=203.0.113.15
MAIL_PORT=25
MAIL_USERNAME=calcenter.fid@find-service.co.id
MAIL_PASSWORD=fid123!!
MAIL_FROM_ADDRESS=calcenter.fid@find-service.co.id
MAIL_FROM_NAME="S3 System"
```

### 7.2 Mail Testing

```bash
# Test email configuration
php artisan tinker
>>> Mail::raw('Test email', function($message) { $message->to('test@example.com')->subject('Test'); });
```

---

## 8. Third-Party Integration

### 8.1 Sentry (Error Tracking)

```bash
composer require sentry/sentry-laravel
```

```env
SENTRY_LARAVEL_DSN=https://xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx@o123456.ingest.sentry.io/1234567
SENTRY_ENVIRONMENT=production
```

### 8.2 New Relic (APM)

```env
NEW_RELIC_LICENSE_KEY=your-license-key
NEW_RELIC_APP_NAME="S3 System"
```

### 8.3 Cloudflare (CDN)

```
- SSL/TLS: Full (strict)
- Always Use HTTPS: ON
- Auto Minify: JS, CSS, HTML
- Brotli: ON
- Cache Level: Standard
```

---

## 9. Deployment Phases

### Phase 1: Infrastructure Setup (Week 1)

```bash
# 1. Setup new database server
ssh admin@db-server
sudo apt update && sudo apt upgrade -y
sudo apt install mysql-server redis-server -y

# 2. Create new database
mysql -u root -p
CREATE DATABASE s3_erp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 's3_app'@'localhost' IDENTIFIED BY 'SecurePassword123!';
GRANT ALL PRIVILEGES ON s3_erp.* TO 's3_app'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# 3. Run migrations
php artisan migrate --database=mysql_new --force

# 4. Seed initial data
php artisan db:seed --class=RoleSeeder
php artisan db:seed --class=PermissionSeeder
php artisan db:seed --class=AdminSeeder
php artisan db:seed --class=EnumMappingSeeder
```

### Phase 2: Backend Deployment (Week 2)

```bash
# 1. Clone repository
git clone https://github.com/your-org/s3-system.git
cd s3-system

# 2. Install dependencies
composer install --no-dev --optimize-autoloader
npm ci

# 3. Build assets
npm run build

# 4. Setup environment
cp .env.example .env
php artisan key:generate

# 5. Run migrations
php artisan migrate --database=mysql_new --force

# 6. Cache config
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 7. Setup queue worker
sudo supervisorctl start laravel-worker:*

# 8. Setup scheduler
echo "* * * * * cd /var/www/s3-system && php artisan schedule:run >> /dev/null 2>&1" | crontab -
```

### Phase 3: Frontend Deployment (Week 2)

```bash
# 1. Deploy to Vercel
vercel --prod

# 2. Or deploy to custom server
npm ci
npm run build
pm2 start npm --name "s3-frontend" -- start
```

### Phase 4: Data Migration (Week 3)

```bash
# 1. Run legacy data migration
php artisan migrate:legacy-tickets
php artisan sync:legacy --table=all

# 2. Verify data integrity
php artisan db:verify --table=tickets
php artisan db:verify --table=units

# 3. Setup dual-write (if enabled)
# Feature flag: FEATURE_DUAL_WRITE=true
```

### Phase 5: Testing & Validation (Week 4)

```bash
# 1. Run tests
php artisan test --parallel

# 2. Run security scan
php artisan security:check

# 3. Run performance test
php artisan benchmark:api

# 4. Validate data integrity
php artisan db:validate --full
```

### Phase 6: Go-Live (Week 5)

```bash
# 1. Enable feature flags
# Set FEATURE_NEW_TICKET=true in .env

# 2. Monitor system
# Check Sentry, New Relic, Grafana

# 3. Decommission legacy (read-only)
# Set legacy system to read-only mode
```

---

## 10. CI/CD Pipeline

### 10.1 GitHub Actions Workflow

```yaml
# .github/workflows/deploy.yml

name: Deploy S3 System

on:
  push:
    branches: [main]
  pull_request:
    branches: [main]

jobs:
  test:
    runs-on: ubuntu-latest
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: root
          MYSQL_DATABASE: s3_erp_test
        ports:
          - 3306:3306
      redis:
        image: redis:7
        ports:
          - 6379:6379

    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          extensions: pdo_mysql, redis, mbstring

      - name: Install dependencies
        run: composer install --no-dev --optimize-autoloader

      - name: Run tests
        run: php artisan test --parallel
        env:
          DB_CONNECTION: mysql
          DB_HOST: 127.0.0.1
          DB_PORT: 3306
          DB_DATABASE: s3_erp_test
          DB_USERNAME: root
          DB_PASSWORD: root

  deploy-backend:
    needs: test
    runs-on: ubuntu-latest
    if: github.ref == 'refs/heads/main'

    steps:
      - uses: actions/checkout@v4

      - name: Deploy to server
        uses: appleboy/ssh-action@v1
        with:
          host: ${{ secrets.SERVER_HOST }}
          username: ${{ secrets.SERVER_USERNAME }}
          key: ${{ secrets.SSH_PRIVATE_KEY }}
          script: |
            cd /var/www/s3-system
            git pull origin main
            composer install --no-dev --optimize-autoloader
            php artisan migrate --database=mysql_new --force
            php artisan config:cache
            php artisan route:cache
            php artisan view:cache
            sudo supervisorctl restart laravel-worker:*

  deploy-frontend:
    needs: test
    runs-on: ubuntu-latest
    if: github.ref == 'refs/heads/main'

    steps:
      - uses: actions/checkout@v4

      - name: Deploy to Vercel
        uses: amondnet/vercel-action@v25
        with:
          vercel-token: ${{ secrets.VERCEL_TOKEN }}
          vercel-org-id: ${{ secrets.VERCEL_ORG_ID }}
          vercel-project-id: ${{ secrets.VERCEL_PROJECT_ID }}
          vercel-args: '--prod'
```

### 10.2 Deployment Script

```bash
#!/bin/bash
# deploy.sh

set -e

echo "Starting deployment..."

# 1. Pull latest code
git pull origin main

# 2. Install dependencies
composer install --no-dev --optimize-autoloader
npm ci

# 3. Build assets
npm run build

# 4. Run migrations
php artisan migrate --database=mysql_new --force

# 5. Cache everything
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 6. Restart queue workers
sudo supervisorctl restart laravel-worker:*

# 7. Clear cache
php artisan cache:clear

# 8. Health check
curl -f http://localhost:8000/health || exit 1

echo "Deployment completed successfully!"
```

---

## 11. Database Migration Strategy

### 11.1 Migration Timeline

```
┌─────────────────────────────────────────────────────────────────┐
│                    Migration Timeline                           │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  Week 1: Infrastructure Setup                                    │
│  ├── Create new database (s3_erp)                                │
│  ├── Run migrations                                              │
│  └── Seed initial data (roles, permissions, admin)              │
│                                                                  │
│  Week 2: Backend Deployment                                      │
│  ├── Deploy Laravel API                                          │
│  ├── Deploy Filament Admin                                       │
│  └── Setup queue workers                                         │
│                                                                  │
│  Week 3: Data Migration                                          │
│  ├── Run legacy data migration                                   │
│  ├── Verify data integrity                                       │
│  └── Setup dual-write (if enabled)                               │
│                                                                  │
│  Week 4: Testing & Validation                                    │
│  ├── Run integration tests                                       │
│  ├── Run security scan                                           │
│  └── Performance testing                                         │
│                                                                  │
│  Week 5: Go-Live                                                 │
│  ├── Enable feature flags                                        │
│  ├── Monitor system                                              │
│  └── Decommission legacy (read-only)                             │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

### 11.2 Migration Commands

```bash
# 1. Create new tables
php artisan migrate --database=mysql_new --force

# 2. Seed initial data
php artisan db:seed --class=RoleSeeder
php artisan db:seed --class=PermissionSeeder
php artisan db:seed --class=AdminSeeder
php artisan db:seed --class=EnumMappingSeeder

# 3. Migrate legacy data
php artisan migrate:legacy-tickets
php artisan sync:legacy --table=all

# 4. Verify data integrity
php artisan db:verify --table=tickets
php artisan db:verify --table=units
php artisan db:verify --table=customers

# 5. Create backward compatibility views
php artisan db:create-views
```

---

## 12. Monitoring & Logging

### 12.1 Health Check Endpoint

```php
// routes/api.php
Route::get('/health', function () {
    $checks = [
        'database' => $this->checkDatabase(),
        'legacy_database' => $this->checkLegacyDatabase(),
        'redis' => $this->checkRedis(),
        'queue' => $this->checkQueue(),
    ];

    $healthy = !in_array(false, $checks, true);

    return response()->json([
        'status' => $healthy ? 'healthy' : 'unhealthy',
        'checks' => $checks,
        'timestamp' => now()->toIso8601String(),
    ], $healthy ? 200 : 503);
});
```

### 12.2 Logging Configuration

```php
// config/logging.php
'channels' => [
    'stack' => [
        'driver' => 'stack',
        'channels' => ['single', 'slack'],
        'ignore_exceptions' => false,
    ],

    'single' => [
        'driver' => 'single',
        'path' => storage_path('logs/laravel.log'),
        'level' => env('LOG_LEVEL', 'debug'),
    ],

    'slack' => [
        'driver' => 'slack',
        'url' => env('LOG_SLACK_WEBHOOK_URL'),
        'username' => 'S3 System Logger',
        'emoji' => ':boom:',
        'level' => 'critical',
    ],

    'audit' => [
        'driver' => 'single',
        'path' => storage_path('logs/audit.log'),
        'level' => 'info',
    ],
],
```

### 12.3 Monitoring Stack

| Tool | Purpose |
|---|---|
| **Laravel Telescope** | Debug & query monitoring (dev) |
| **Sentry** | Error tracking & performance monitoring |
| **New Relic** | APM & server monitoring |
| **Grafana** | Metrics visualization |
| **Prometheus** | Metrics collection |

---

## 13. Rollback Strategy

### 13.1 Feature Flags

```php
// config/features.php
return [
    'use_new_ticket_module' => env('FEATURE_NEW_TICKET', false),
    'use_new_loan_module' => env('FEATURE_NEW_LOAN', false),
    'use_new_dashboard' => env('FEATURE_NEW_DASHBOARD', false),
    'dual_write_enabled' => env('FEATURE_DUAL_WRITE', false),
];
```

### 13.2 Rollback Procedures

```bash
# 1. Disable feature flags
php artisan config:cache
# Set FEATURE_NEW_TICKET=false in .env

# 2. Rollback database (if needed)
php artisan migrate:rollback --database=mysql_new --step=1

# 3. Clear cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# 4. Restart services
sudo supervisorctl restart laravel-worker:*
sudo systemctl restart php8.2-fpm
```

### 13.3 Emergency Rollback

```bash
#!/bin/bash
# emergency-rollback.sh

echo "EMERGENCY ROLLBACK INITIATED"

# 1. Disable all feature flags
sed -i 's/FEATURE_NEW_TICKET=true/FEATURE_NEW_TICKET=false/' .env
sed -i 's/FEATURE_NEW_LOAN=true/FEATURE_NEW_LOAN=false/' .env
sed -i 's/FEATURE_NEW_DASHBOARD=true/FEATURE_NEW_DASHBOARD=false/' .env
sed -i 's/FEATURE_DUAL_WRITE=true/FEATURE_DUAL_WRITE=false/' .env

# 2. Clear cache
php artisan cache:clear
php artisan config:clear

# 3. Restart services
sudo supervisorctl restart laravel-worker:*

# 4. Verify health
curl -f http://localhost:8000/health || echo "HEALTH CHECK FAILED"

echo "ROLLBACK COMPLETED"
```

---

## 14. Disaster Recovery Plan

### 14.1 RTO/RPO Targets

| Metric | Target | Description |
|---|---|---|
| **RTO** | 4 hours | Recovery Time Objective |
| **RPO** | 1 hour | Recovery Point Objective |

### 14.2 Backup Strategy

```bash
#!/bin/bash
# backup.sh

DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/var/backups/s3-system"

# 1. Database backup
mysqldump -u s3_app -p'SecurePassword123!' s3_erp | gzip > $BACKUP_DIR/db_$DATE.sql.gz

# 2. File backup
tar -czf $BACKUP_DIR/files_$DATE.tar.gz /var/www/s3-system/storage

# 3. Upload to S3
aws s3 cp $BACKUP_DIR/db_$DATE.sql.gz s3://s3-system-backups/
aws s3 cp $BACKUP_DIR/files_$DATE.tar.gz s3://s3-system-backups/

# 4. Cleanup old backups (keep 30 days)
find $BACKUP_DIR -type f -mtime +30 -delete
```

### 14.3 Backup Schedule

| Type | Frequency | Retention |
|---|---|---|
| Database | Daily | 30 days |
| Files | Weekly | 90 days |
| Full | Monthly | 1 year |

### 14.4 Recovery Procedures

```bash
# 1. Restore database
gunzip < db_20260930_120000.sql.gz | mysql -u s3_app -p s3_erp

# 2. Restore files
tar -xzf files_20260930_120000.tar.gz -C /var/www/s3-system/

# 3. Clear cache
php artisan cache:clear
php artisan config:clear

# 4. Restart services
sudo supervisorctl restart laravel-worker:*
```

---

## 15. Scaling Strategy

### 15.1 Horizontal Scaling

```
┌─────────────────────────────────────────────────────────────────┐
│                    Horizontal Scaling                            │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  Load Balancer                                                   │
│       │                                                          │
│       ├──► App Server 1 (Docker)                                │
│       ├──► App Server 2 (Docker)                                │
│       └──► App Server 3 (Docker)                                │
│                                                                  │
│  Database: Primary-Replica                                       │
│       ├──► MySQL Primary (Write)                                │
│       └──► MySQL Replica (Read)                                 │
│                                                                  │
│  Cache: Redis Cluster                                            │
│       ├──► Redis Master                                         │
│       └──► Redis Replica                                        │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

### 15.2 Vertical Scaling

| Phase | CPU | RAM | Storage | When |
|---|---|---|---|---|
| **Initial** | 2 vCPU | 4 GB | 50 GB | Launch |
| **Growth** | 4 vCPU | 8 GB | 100 GB | 1000+ users |
| **Scale** | 8 vCPU | 16 GB | 200 GB | 5000+ users |

### 15.3 Auto-Scaling Triggers

```yaml
# Auto-scaling rules
scaling:
  cpu_threshold: 70%
  memory_threshold: 80%
  scale_up: +1 instance
  scale_down: -1 instance
  min_instances: 2
  max_instances: 10
```

---

## 16. Cost Estimation

### 16.1 Monthly Cost Estimate

| Component | Specification | Monthly Cost (USD) |
|---|---|---|
| **API Server** | 2 vCPU, 4GB RAM | $40 |
| **Database Server** | 4 vCPU, 8GB RAM | $80 |
| **Redis** | 2GB | $20 |
| **CDN** | Cloudflare Pro | $20 |
| **Object Storage** | 100GB S3 | $5 |
| **Monitoring** | Sentry + New Relic | $50 |
| **SSL** | Let's Encrypt | $0 |
| **Domain** | .co.id | $15 |
| **Total** | | **$230** |

### 16.2 Cost Optimization

- Use reserved instances for predictable workloads
- Enable auto-scaling to reduce idle capacity
- Use Cloudflare free tier for CDN
- Monitor and optimize database queries

---

## 17. Runbook

### 17.1 Common Operations

```bash
# Check application status
php artisan about

# Check queue status
php artisan queue:monitor

# Check schedule status
php artisan schedule:list

# Clear all caches
php artisan optimize:clear

# Run migrations
php artisan migrate --force

# Seed database
php artisan db:seed

# Create new admin user
php artisan admin:create

# Generate API docs
php artisan l5-swagger:generate
```

### 17.2 Troubleshooting

```bash
# Check logs
tail -f storage/logs/laravel.log

# Check queue workers
sudo supervisorctl status

# Check database connection
php artisan tinker
>>> DB::connection()->getPdo();

# Check Redis connection
php artisan tinker
>>> Redis::ping();

# Check disk space
df -h

# Check memory usage
free -m
```

---

## 18. Testing Plan

### 18.1 Test Structure

```
tests/
├── Unit/
│   ├── Models/
│   │   ├── TicketTest.php
│   │   ├── UnitTest.php
│   │   └── UserTest.php
│   ├── Services/
│   │   ├── TicketServiceTest.php
│   │   └── PreventiveMaintenanceServiceTest.php
│   └── Http/
│       └── Controllers/
│           └── Api/
│               └── TicketControllerTest.php
├── Feature/
│   ├── Api/
│   │   ├── TicketApiTest.php
│   │   ├── UnitApiTest.php
│   │   └── AuthApiTest.php
│   └── Webhook/
│       └── LegacyWebhookTest.php
└── Browser/
    └── (Dusk tests)
```

### 18.2 Test Commands

```bash
# Run all tests
php artisan test

# Run with coverage
php artisan test --coverage

# Run specific test
php artisan test --filter=TicketApiTest

# Run in parallel
php artisan test --parallel
```

---

## 19. Security Checklist

| Area | Action |
|---|---|
| **SSL/TLS** | Enable HTTPS for all endpoints |
| **Firewall** | Restrict database access to internal network |
| **Secrets** | Use environment variables, never commit to git |
| **Rate Limiting** | Enable throttling on all API endpoints |
| **CORS** | Configure strict CORS policies |
| **CSRF** | Enable CSRF protection for Filament |
| **XSS** | Sanitize all user inputs |
| **SQL Injection** | Use Eloquent ORM (parameter binding) |
| **Authentication** | Use Sanctum tokens with expiration |
| **Authorization** | Implement RBAC with permissions |
| **Audit Logging** | Log all create/update/delete operations |
| **Backup** | Daily automated backups |

---

## 20. Go-Live Checklist

- [ ] Infrastructure setup complete
- [ ] Database migrations run
- [ ] Seed data loaded
- [ ] Backend deployed
- [ ] Frontend deployed
- [ ] SSL certificates installed
- [ ] CDN configured
- [ ] Monitoring active
- [ ] Backup strategy in place
- [ ] Health check passing
- [ ] Load testing complete
- [ ] Security scan passed
- [ ] Documentation complete
- [ ] Team trained
- [ ] Rollback plan tested
- [ ] Feature flags configured
- [ ] Legacy system in read-only mode
- [ ] Disaster recovery plan tested
- [ ] Runbook complete
- [ ] Cost estimation approved

---

*Spesifikasi ini dapat dilanjutkan dengan testing plan dan runbook detail.*
