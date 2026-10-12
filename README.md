# HackaTone Health

Laravel 13 + Livewire 4 + Tailwind 4. See `CLAUDE.md` for the product and domain rules.

## Local

```sh
composer install && npm install
cp .env.example .env && php artisan key:generate
# set HH_ADMIN_EMAIL and HH_ADMIN_PASSWORD in .env first
php artisan migrate --seed   # admin from .env; locally also demo users aziz@example.com and malika@example.com (password: password)
npm run build
php artisan serve
```

Mail uses the `log` mailer locally: verification links are written to `storage/logs/laravel.log`.

To skip the email / Telegram confirmation while developing, set `HH_SKIP_CONFIRMATION=true` (only works with `APP_ENV=local`).

## Shared hosting

1. Database: development uses SQLite (default `.env`); production uses MySQL. On the server set `DB_CONNECTION=mysql` and `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` in `.env` (database charset `utf8mb4`).
2. Upload the project, point the domain's document root to `public/`, run `composer install --no-dev -o`, `php artisan migrate --force`, and upload `public/build` (built locally with `npm run build`).
3. Create the first admin: set `HH_ADMIN_EMAIL` and `HH_ADMIN_PASSWORD` in `.env` and run `php artisan db:seed --force` (demo users are only seeded locally). Or run `php artisan hh:admin you@example.com`.
4. Add one cron job that runs every minute:
   ```
   * * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
   ```
   It only sends daily Telegram reminders to users (`reminders:send`). Bot messages come through the webhook.
5. In the admin panel, open **Settings**: paste the bot token from @BotFather, **Save** (a webhook secret is created if empty), press **Check token** (fills in the username), then **Set webhook**. The site must be on HTTPS. Add admin chat IDs for registration alerts.
6. Make `public/uploads` writable (activity GIFs are stored there; no `storage:link` needed). Admin uploads are limited by `HH_MEDIA_MAX_KB` (default 2048) and by PHP's `upload_max_filesize`.
7. Optional, AI animations: in **Settings → AI animations**, paste a Hugging Face token with the "Make calls to Inference Providers" permission. Then use **Activity videos → Generate with AI**; watch the draft, then **Make GIF and publish** (the browser converts it to a small GIF).
8. Set real mail settings (`MAIL_*` in `.env`) so verification emails are delivered.

## Commands

| Command | What it does |
| --- | --- |
| `php artisan hh:admin {email}` | Create an admin, or promote an existing user |
| `php artisan reminders:send [--dry-run]` | Send today's reminders to linked clients whose time has come |
| `php artisan telegram:poll` | Local development only: read bot messages by hand (remove the webhook first) |
| `php artisan telegram:webhook [--delete]` | Register or remove the webhook |
| `php artisan test` | Run the tests |
# hackatone
