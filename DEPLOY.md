# Deploying FormForge to Render (step by step)

The repo already contains everything Render needs:

| File | What it does |
|---|---|
| `Dockerfile` | Builds the app: PHP 8.2 + Apache, Composer packages, Vite/Tailwind assets |
| `docker/start.sh` | On every boot: caches config, runs migrations, starts the **queue worker** (AI + imports), then Apache |
| `render.yaml` | Blueprint that creates the web service **and** a free PostgreSQL database |

Everything runs on Render's **free plan**. The queue worker runs inside the web container, because Render's free plan has no separate background workers.

---

## Step 1: Push the code to GitHub

The latest code must be on GitHub (`https://github.com/AmanKabra1/formforge`):

```bash
git push origin master
```

## Step 2: Generate an APP_KEY (on your computer)

In the project folder, run:

```bash
php artisan key:generate --show
```

Copy the whole line it prints. It looks like `base64:AbC123...=`. You'll paste it in Step 4.

## Step 3: Create the Blueprint on Render

1. Go to **https://dashboard.render.com** and sign up or log in with **GitHub**.
2. Click **New +** → **Blueprint**.
3. Connect your GitHub account if asked, then select the **formforge** repo.
4. Render reads `render.yaml` and shows two resources:
   - **formforge** (Web Service, Docker, Free)
   - **formforge-db** (PostgreSQL, Free)

## Step 4: Fill in the secret values

Render asks for the values that aren't stored in the repo:

| Variable | Value |
|---|---|
| `APP_KEY` | the `base64:...` line from Step 2 |
| `GROQ_API_KEY` | your Groq key (`gsk_...`) |
| `GEMINI_API_KEY` | your Google AI Studio key (optional; leave empty if you're only using Groq) |

Click **Apply**.

## Step 5: Wait for the first deploy (about 5–10 minutes)

Open **formforge → Logs**. You should see:

```
INFO  Running migrations.
  ... create_forms_table ......... DONE
Apache/2.4 configured -- resuming normal operations
```

When the status turns **Live**, your URL is shown at the top, for example `https://formforge-xxxx.onrender.com`.
`APP_URL` is picked up automatically from Render, so you don't need to set it.

## Step 6: Use it

1. Open the URL and click **Create an account**.
2. Click **Generate** on the dashboard and try a prompt. Groq usually answers in a few seconds.
3. Set a form to **Published**, save it, and share its `/f/...` link.

---

## Switching the AI provider

In Render, go to **formforge → Environment** and change `AI_PROVIDER`:

- `groq`: uses `GROQ_API_KEY` and `GROQ_MODEL` (default `openai/gpt-oss-120b`)
- `gemini`: uses `GEMINI_API_KEY` and `GEMINI_MODEL` (default `gemini-2.5-flash`)

Save, and Render redeploys automatically.

## Updating the live site

Every `git push origin master` redeploys automatically (`autoDeploy: true`).

## Good to know about the free plan

- **Sleeps after 15 minutes idle.** The first visit after that takes about 30–60 seconds to wake up.
- **The free Postgres database expires after 30 days.** Before that, upgrade it (Basic, about $6/month), or create a free database at [neon.tech](https://neon.tech) and put its connection string into `DB_URL`.
- **Uploaded files are temporary.** Uploads (file answers and imports) are stored on the container disk, which is wiped on each deploy. Form data in Postgres is safe.
- Don't run `php artisan db:seed` in production. It creates `demo@example.com` / `password`.

## Troubleshooting

| Problem | Fix |
|---|---|
| **500 error** + `Unsupported cipher or incorrect key length` in logs | `APP_KEY` is wrong. Paste the full `base64:...` value from Step 2 |
| AI stays on "Reading your prompt…" forever | Check the logs for `Groq API error`. The key may be wrong, or `AI_PROVIDER` doesn't match the key you set |
| Page looks unstyled | Hard refresh (Ctrl+F5). If it persists, check that the build log shows `vite build` succeeding |
| Deploy fails at `migrate` | Make sure `DB_URL` is linked to `formforge-db` (Environment tab) |

## Running the production image locally (optional)

```bash
docker build -t formforge .
docker run -p 8080:10000 -e APP_KEY=base64:... -e DB_CONNECTION=pgsql -e DB_URL=postgresql://user:pass@host/db formforge
```
