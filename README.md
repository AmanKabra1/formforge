# FormForge

**AI-powered form builder.** Describe a form in plain English and AI builds it. Then drag and drop to refine it, publish it with a shareable link, and collect and analyse responses.

Built with Laravel 12, Livewire 3 (Volt), Alpine.js and Tailwind CSS.

## Features

- **AI generation.** Describe a form ("job application with CV upload") and get the fields, choices and validation. You can also ask AI to edit an existing form. Works with OpenAI or Claude.
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
AI_PROVIDER=openai        # or: claude
OPENAI_API_KEY=sk-...
CLAUDE_API_KEY=sk-ant-...
```

Without a key, everything works except AI generation.

## Tests

```bash
php artisan test
```

## Deploying

See [DEPLOY.md](DEPLOY.md).
