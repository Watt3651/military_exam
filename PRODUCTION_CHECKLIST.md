# Production Checklist

## 1) Environment

- [ ] Copy `.env.example.production` to `.env`
- [ ] Set `APP_ENV=production`
- [ ] Set `APP_DEBUG=false`
- [ ] Set real `APP_URL`
- [ ] Generate key if needed: `php artisan key:generate --force`

## 2) Database

- [ ] Set production DB credentials
- [ ] Confirm DB user permissions (SELECT/INSERT/UPDATE/DELETE/ALTER/INDEX)
- [ ] Run migration: `php artisan migrate --force`

## 3) Mail

- [ ] Set SMTP host/port/user/password
- [ ] Set sender address and sender name
- [ ] Verify outbound mail from server (TLS/Firewall)

## 4) Backups (Spatie)

- [ ] Set backup-related env values in `.env`
- [ ] Confirm backup destination/storage
- [ ] Test database-only backup: `php artisan backup:run --only-db`
- [ ] Test full backup: `php artisan backup:run`
- [ ] Test cleanup command: `php artisan backup:clean`
- [ ] Test monitor command: `php artisan backup:monitor`
- [ ] Verify backup files exist on configured disk/path
- [ ] Verify backup success/failure notifications are delivered

## 5) Build and caches

- [ ] Run `composer install --optimize-autoloader --no-dev`
- [ ] Run `php artisan config:cache`
- [ ] Run `php artisan route:cache`
- [ ] Run `php artisan view:cache`
- [ ] Run `php artisan queue:restart`

## 6) Scheduler (cron)

- [ ] Add cron entry:

```cron
* * * * * cd /var/www/millitary_exam && php artisan schedule:run >> /dev/null 2>&1
```

- [ ] Verify scheduled jobs are registered: `php artisan schedule:list`

## 7) Workers/Monitoring

- [ ] Run queue worker process manager (Supervisor/systemd)
- [ ] Verify logs and alerts
- [ ] Verify storage permissions (`storage`, `bootstrap/cache`)
- [ ] Check HTTPS and web server config
