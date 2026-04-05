# Clienthub — Deployment Guide

This guide covers everything needed to take Clienthub from development to a
live production server. Follow the sections in order on your first deploy.

---

## Prerequisites

| Requirement | Version | Notes |
|-------------|---------|-------|
| PHP | 8.2 or 8.3+ | Extensions: BCMath, Ctype, Fileinfo, JSON, Mbstring, OpenSSL, PDO, Tokenizer, XML |
| MySQL | 8.0+ | Or PostgreSQL 14+. SQLite is for development only. |
| Node.js | 18+ | For building frontend assets |
| Composer | 2.x | |
| A domain | — | With an SSL certificate (Let's Encrypt is free) |

---

## Step 1 — Clone and install dependencies

```bash
git clone <your-repo-url> /var/www/clienthub
cd /var/www/clienthub

composer install --no-dev --optimize-autoloader
npm ci && npm run build
```

---

## Step 2 — Environment file

```bash
cp .env.example .env
```

Open `.env` and fill in every value marked `<...>`. Key ones:

```ini
APP_KEY=                    # generated in Step 3
APP_URL=https://yourdomain.com
DB_DATABASE=clienthub
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password
MAIL_HOST=smtp.yourhost.com
MAIL_USERNAME=your_mail_user
MAIL_PASSWORD=your_mail_password
MAIL_FROM_ADDRESS=no-reply@yourdomain.com
```

### Testing email with Mailtrap (before switching to production mail)

Sign up at [mailtrap.io](https://mailtrap.io) → Inboxes → SMTP Settings.
Set these values in `.env`:

```ini
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=<your_mailtrap_username>
MAIL_PASSWORD=<your_mailtrap_password>
MAIL_SCHEME=null
```

All outgoing emails (invitations, invoices, messages) will appear in your
Mailtrap inbox instead of being delivered — perfect for testing the full
email flow without affecting real users.

### Switching to your hosting provider's mail

When your hosting provider gives you SMTP credentials, simply replace the
Mailtrap values:

```ini
MAIL_HOST=smtp.yourhost.com   # from your host's mail panel
MAIL_PORT=587                 # 587 (STARTTLS) or 465 (SSL)
MAIL_USERNAME=no-reply@yourdomain.com
MAIL_PASSWORD=your_smtp_password
MAIL_SCHEME=null              # null for 587, "tls" for 465
MAIL_FROM_ADDRESS=no-reply@yourdomain.com
```

No code changes needed — only `.env` changes.

---

## Step 3 — Generate application key

```bash
php artisan key:generate
```

This writes a random `APP_KEY` into your `.env`. Keep this secret — it
encrypts sessions and signed URLs.

---

## Step 4 — Database setup

Create the database and user in MySQL:

```sql
CREATE DATABASE clienthub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'clienthub_user'@'localhost' IDENTIFIED BY 'strong_password_here';
GRANT ALL PRIVILEGES ON clienthub.* TO 'clienthub_user'@'localhost';
FLUSH PRIVILEGES;
```

Run migrations:

```bash
php artisan migrate --force
```

---

## Step 5 — File permissions

```bash
chown -R www-data:www-data /var/www/clienthub
chmod -R 755 /var/www/clienthub
chmod -R 775 /var/www/clienthub/storage
chmod -R 775 /var/www/clienthub/bootstrap/cache
```

---

## Step 6 — Optimise for production

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

> **Important:** Run these commands every time you deploy new code.
> Clear them with `php artisan optimize:clear` if you change `.env` without
> redeploying.

---

## Step 7 — Web server (Nginx)

Create `/etc/nginx/sites-available/clienthub`:

```nginx
server {
    listen 80;
    server_name yourdomain.com www.yourdomain.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name yourdomain.com www.yourdomain.com;

    root /var/www/clienthub/public;
    index index.php;

    ssl_certificate     /etc/letsencrypt/live/yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/yourdomain.com/privkey.pem;

    add_header X-Content-Type-Options nosniff;
    add_header X-Frame-Options SAMEORIGIN;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Never expose storage directly — files are served through Laravel
    location /storage {
        deny all;
    }
}
```

Enable and reload:

```bash
ln -s /etc/nginx/sites-available/clienthub /etc/nginx/sites-enabled/
nginx -t && systemctl reload nginx
```

### SSL certificate (Let's Encrypt — free)

```bash
apt install certbot python3-certbot-nginx
certbot --nginx -d yourdomain.com -d www.yourdomain.com
```

Certbot auto-renews. Verify renewal works: `certbot renew --dry-run`

---

## Step 8 — Queue worker (Supervisor)

Clienthub queues all outgoing mail. Without a running queue worker,
**no emails will ever be sent**. Supervisor keeps the worker alive.

Install Supervisor:

```bash
apt install supervisor
```

Create `/etc/supervisor/conf.d/clienthub-worker.conf`:

```ini
[program:clienthub-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/clienthub/artisan queue:work database --sleep=3 --tries=3 --timeout=60 --max-jobs=500
directory=/var/www/clienthub
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2                  ; 2 workers — increase for higher email volume
redirect_stderr=true
stdout_logfile=/var/log/clienthub-worker.log
stopwaitsecs=120
```

Start it:

```bash
supervisorctl reread
supervisorctl update
supervisorctl start clienthub-worker:*
supervisorctl status          # should show RUNNING
```

### After every deployment

```bash
php artisan queue:restart     # gracefully restarts workers with new code
supervisorctl restart clienthub-worker:*
```

---

## Step 9 — Scheduler (Cron)

Add this cron entry for the `www-data` user:

```bash
crontab -u www-data -e
```

Add the line:

```cron
* * * * * cd /var/www/clienthub && php artisan schedule:run >> /dev/null 2>&1
```

This runs Laravel's scheduler every minute. It handles:
- Pruning old queued jobs
- Pruning failed jobs older than 7 days
- Future: invoice reminders, recurring tasks

Verify the scheduler is working:

```bash
php artisan schedule:list
```

---

## Step 10 — Failed job monitoring

Create the failed jobs table (if not already migrated):

```bash
php artisan queue:failed-table
php artisan migrate
```

Check for failed jobs anytime:

```bash
php artisan queue:failed
php artisan queue:retry all   # retry all failed jobs
```

---

## Deployment checklist (repeat on every code update)

```bash
cd /var/www/clienthub
git pull origin main

composer install --no-dev --optimize-autoloader
npm ci && npm run build

php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
```

---

## Environment variable quick reference

| Variable | Required | What it controls |
|----------|----------|-----------------|
| `APP_KEY` | ✅ Yes | Session and URL signing encryption |
| `APP_URL` | ✅ Yes | Used in invitation email links |
| `APP_DEBUG` | ✅ Yes | Must be `false` in production |
| `DB_*` | ✅ Yes | Database connection |
| `MAIL_*` | ✅ Yes | All outgoing email |
| `QUEUE_CONNECTION` | ✅ Yes | Set to `database` or `redis` |
| `SESSION_SECURE_COOKIE` | ✅ Yes | Must be `true` with HTTPS |
| `SESSION_ENCRYPT` | ✅ Yes | Encrypts session data at rest |
| `FILESYSTEM_DISK` | — | `local` (single server) or `s3` (cloud) |
| `AWS_*` | Only if S3 | Cloud file storage |
| `REDIS_*` | Optional | Better performance than DB driver |
| `BCRYPT_ROUNDS` | — | Default 12 is fine |

---

## Testing the full stack before going live

With Mailtrap configured, run through this flow manually:

1. **Register** as an owner at `/register` → check Mailtrap for nothing (no welcome email yet)
2. **Invite a client** at `/clients` → Mailtrap should receive the invitation email
3. **Open the invitation link** from Mailtrap → set password → lands on client dashboard
4. **Invite staff** at `/staff` → Mailtrap receives staff invitation
5. **Create a project** → assign to the client
6. **Send a message** on the project → Mailtrap receives notification to client
7. **Create and send an invoice** → Mailtrap receives invoice email to client
8. **Log in as the client** at `/client/login` → verify project, invoice, messages visible
9. **Check activity log** at `/activity` (as owner) → all actions recorded

---

## Switching from Mailtrap to production mail

1. Get SMTP credentials from your hosting provider's mail panel
2. Update these values in `/var/www/clienthub/.env`:
   ```ini
   MAIL_HOST=smtp.yourhost.com
   MAIL_PORT=587
   MAIL_USERNAME=no-reply@yourdomain.com
   MAIL_PASSWORD=your_smtp_password
   MAIL_FROM_ADDRESS=no-reply@yourdomain.com
   ```
3. Run `php artisan config:cache` to apply the change
4. Restart queue workers: `php artisan queue:restart`
5. Send a test invitation to verify real delivery

---

## Common issues

| Problem | Likely cause | Fix |
|---------|-------------|-----|
| Emails not sending | Queue worker not running | Check `supervisorctl status` |
| Invitation links broken | `APP_URL` wrong | Set `APP_URL` to exact production URL including `https://` |
| Files not uploading | Wrong permissions | `chmod -R 775 storage` |
| 500 errors | `APP_DEBUG=false` hides details | Check `storage/logs/laravel.log` |
| Sessions not persisting | `SESSION_SECURE_COOKIE=true` on HTTP | Add HTTPS or set to `false` for local dev |
| Caches stale after deploy | Forgot to run cache commands | Run `php artisan optimize:clear && php artisan optimize` |
