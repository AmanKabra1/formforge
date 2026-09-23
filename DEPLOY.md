# Deploying FormForge

FormForge is a standard Laravel app. Any host that runs it needs three things:

1. **A web process** that serves `public/`.
2. **A queue worker** (`php artisan queue:work`). AI generation and document import run as queued jobs, and they never finish without a worker.
3. **Persistent storage** for the database and uploaded files. Many hosts wipe the disk on every deploy, so use a managed MySQL or Postgres database there rather than SQLite.

## Environment variables (all hosts)

```dotenv
APP_NAME=FormForge
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:...            # generate locally: php artisan key:generate --show
APP_URL=https://your-domain.com

DB_CONNECTION=pgsql           # or mysql; fill in the host's DB_HOST / DB_PORT / DB_DATABASE / DB_USERNAME / DB_PASSWORD
QUEUE_CONNECTION=database
SESSION_DRIVER=database
CACHE_STORE=database

AI_PROVIDER=openai            # or claude
OPENAI_API_KEY=...            # or CLAUDE_API_KEY=...
```

Never commit `.env`. It is already in `.gitignore`. Set these in the host's dashboard.

## Build and release commands

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan storage:link
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

Don't run `db:seed` in production. It creates a `demo@example.com` account with the password `password`.

---

## Option A: Railway (easiest)

1. Sign up at railway.com, choose **New Project → Deploy from GitHub repo**, and pick `formforge`.
2. Add a **PostgreSQL** database to the project and copy its connection variables into the app's variables, along with the ones above.
3. In the service settings:
   - **Build command:** `composer install --no-dev --optimize-autoloader && npm ci && npm run build`
   - **Start command:** `php artisan migrate --force && php artisan config:cache && php artisan serve --host=0.0.0.0 --port=$PORT`
4. Add a **second service** from the same repo with the start command `php artisan queue:work --tries=3`. This is the queue worker.
5. Under **Settings → Networking**, generate a domain, then set `APP_URL` to it.

Cost: there is a trial credit, then roughly $5/month for small usage.

## Option B: Render

1. Create a **Web Service** from the GitHub repo using the PHP/Docker runtime. You'll need a Dockerfile based on `php:8.2-apache` or `serversideup/php`.
2. Create a **Render Postgres** database and link its variables.
3. Create a **Background Worker** from the same repo with the command `php artisan queue:work`.
4. Note: free web services sleep after 15 minutes idle, so the first request after that is slow.

## Option C: Your own VPS (DigitalOcean, Hetzner, AWS Lightsail…)

1. Install Nginx, PHP 8.2 (with the `pdo_sqlite`/`pdo_mysql`, `zip`, `gd`, `mbstring`, `xml` extensions), Composer and Node.
2. Clone the repo, create `.env`, and run the build and release commands above.
3. Point the Nginx root at `public/` and add HTTPS with Certbot.
4. Keep the queue worker running with Supervisor:

   ```ini
   [program:formforge-worker]
   command=php /var/www/formforge/artisan queue:work --sleep=3 --tries=3
   autostart=true
   autorestart=true
   user=www-data
   ```

Laravel Forge or Ploi can do all of Option C for you for a monthly fee.

---

## After it's live

- Register your own account (don't use the demo one).
- Create a form, set it to **Published**, and share the `/f/...` link.
- Check that AI generation completes. If it hangs on "AI is generating…", the queue worker isn't running.
