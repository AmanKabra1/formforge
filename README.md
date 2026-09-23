# FormForge

**AI-powered form builder.** Describe a form in plain English and AI builds it. Then drag and drop to refine it, publish it with a shareable link, and collect and analyse responses.

Built with Laravel 12, Livewire 3 (Volt), Alpine.js and Tailwind CSS.

## Features

- **AI generation.** Describe a form ("job application with CV upload") and get the fields, choices and validation. You can also ask AI to edit an existing form. Works with Groq (default), Google Gemini, OpenAI or Claude.
- **Drag-and-drop builder.** 12 field types (text, long text, number, email, phone, date, dropdown, single choice, checkboxes, rating, file upload, section heading). Drag fields in from the palette, reorder them, and use the live desktop and mobile preview.
- **Import.** Turn a Word questionnaire (`.docx`) or an Excel sheet (`.xlsx`) into a form.
- **Version history.** Every save creates a snapshot you can restore with one click.
- **Raw JSON editor** for editing the schema directly, with validation.
- **Public forms** at `/f/{slug}`, with a progress bar, validation and a success screen.
- **Responses.** Searchable table, full-response drawer, CSV export, and an **Insights** tab (responses per day, answer breakdowns).
- **Quality of life.** Ctrl+K quick search, Ctrl+S to save, toasts, unsaved-changes warnings.

## Run it locally

Requirements: PHP 8.2+, Composer, Node 18+.

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed      # creates demo@example.com / password
npm run build                   # or: npm run dev
php artisan serve               # http://localhost:8000
php artisan queue:work          # in a second terminal: needed for AI generation and imports
```

On Windows you can instead run `start.bat` (or `start.ps1`), which does the setup and starts the server. You still need to run `php artisan queue:work` separately.

### AI setup

Add one of these to `.env`:

```dotenv
AI_PROVIDER=groq          # groq | gemini | openai | claude
GROQ_API_KEY=gsk_...      # free key: https://console.groq.com/keys
GEMINI_API_KEY=...        # free key: https://aistudio.google.com/apikey
```

Models can be changed with `GROQ_MODEL` (default `openai/gpt-oss-120b`) and `GEMINI_MODEL` (default `gemini-2.5-flash`).

> **Windows note:** if AI fails with `cURL error 60: SSL certificate problem`, PHP has no CA bundle. Download https://curl.se/ca/cacert.pem and set `curl.cainfo` and `openssl.cafile` to its path in `php.ini`.

Without a key, everything works except AI generation.

## Tests

```bash
php artisan test
```

## Deploying

One-click deploy to Render (free plan) with the included `render.yaml` blueprint. See [DEPLOY.md](DEPLOY.md) for the step-by-step guide.
