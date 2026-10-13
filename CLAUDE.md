# HackaTone Health

Web health system for Uzbekistan. A client registers, the system compares height and weight with WHO references for their age, and gives a weekly training plan and a daily feeding menu at three levels (Easy, Mid, Strong). Reminders are sent through our Telegram bot.

Stack: Laravel / PHP. Languages: Uzbek (Latin), English, Russian.

## Where the reference implementation lives

`.claude/samples/` holds a working single-page prototype. Treat it as the spec for UI, copy and calculations when building the Laravel version.

- `.claude/hackatone-health.html` is the full prototype in one file (the split `src/` folder below is not in the repo). `.claude/hackatone-health-school.html` is the same prototype with a school theme.
- `.claude/samples/src/` has the same code split into parts:
  - `core.js`: WHO tables, analysis, calorie plan, training patterns, menus, achievement period simulation.
  - `ui.js`, `dash.js`, `flow.js`: state, dashboard views, landing page and registration wizard.
  - `lang-en.js`, `lang-uz.js`, `lang-ru.js`: all UI text.

The prototype keeps data in the browser only. The Laravel version must move it to the database.

## Registration flow

Steps: phone, name, about you (date of birth and gender), body (weight and height), review, then Telegram link.

Validation rules:
- Phone: 9 to 15 digits. Nine digits are treated as Uzbek and get the +998 prefix. Display as `+998 90 123 45 67`.
- Age: 5 to 100 years. Under 5 is refused, use WHO Child Growth Standards with a paediatrician.
- Weight: 10 to 300 kg. Height: 80 to 250 cm.
- Gender: male or female (needed to pick the WHO curve).

## Domain rules (do not change without asking)

- Growth data is the official WHO 2007 reference (ages 5 to 19): L, M, S per month in `app/Services/Health/Data/who2007.php`, generated from WHO's expanded tables (who.int, growth reference data for 5 to 19 years). Uzbekistan's national child nutrition surveys use this same reference; no separate public national tables were found. Z-scores follow WHO's computation guide (LMS, fixed SD beyond +/-3 for BMI); tests check WHO's worked examples. The prototype's `core.js` curves were approximations and are no longer used. Adults use WHO adult BMI categories.
- Youth BMI range: -2 SD to +1 SD of BMI-for-age; above +1 SD overweight, above +2 SD obesity (WHO 2007). Adults: 18.5 to 24.9 healthy, 30 and above obesity.
- Growth window: girls until about 17, boys until about 19. Projected adult height keeps the same z-score: adult median + z x adult SD (WHO values at 19), usually within 5 cm. Typical growth speed is the slope of the WHO median height curve.
- Energy: WHO/FAO Schofield equations times an activity factor: Easy 1.45, Mid 1.65, Strong 1.85.
- Under-19s: never cut calories. Add 5% only when underweight. Adults: +10% underweight, -10% overweight, -15% obesity range.
- Protein per kg: adults 1.0 / 1.3 / 1.6, youth 1.0 / 1.1 / 1.2 (Easy / Mid / Strong). Fat 28% of energy, carbs the rest.
- Weekly training minutes: Easy 150, Mid 285, Strong 375. Youth should reach an average of 60 min a day, so the plan tells them how much free play to add.
- Height achievement periods use an assumption that is ours, not WHO: the level scales the typical growth pace to 90% (Easy), 100% (Mid), 105% (Strong). Keep the on-screen disclaimer that no programme can promise centimetres.
- Mental growth (school pupils): the calendar day form starts with "pages read today". Points for one logged day (`App\Services\Clients\Points`, max 40): training done +10, full planned minutes +5, each meal +2 (max 5), water goal +5, reading +1 per 5 pages (max +10). Never rank by height, weight or BMI.
- This is an educational product. Keep the disclaimer: not medical advice, parent or guardian registers minors.

## Telegram linking

Current flow (the owner's spec): after registration the confirm page shows the secret 6-digit code and a QR / link `https://t.me/<bot>?start=<code>`. In the bot: the code (from /start or typed), then the mobile number via the contact button. Link only if the shared contact is the same Telegram account (`contact.user_id == from.id`) and the code belongs to the client with that mobile number; then save the Telegram id. The confirm page polls and opens the user's panel. No language step: the bot uses the client's site language.

Telegram Web App: `/tg` opens inside Telegram (bot menu button, set in Admin > Settings, and an "Open app" button after linking; needs HTTPS). `/tg/auth` checks Telegram's signed initData (HMAC with the bot token, max 24 h old) and signs in the linked client.

### Original brief

At the end of registration the client sees a 6-digit code and a QR code that opens `https://t.me/<bot_username>?start=<code>`.

Server side:
1. On registration, save the code with the phone, language, level and reminder settings. Codes must expire (suggest 15 minutes) and be single use.
2. The bot handles `/start <code>` and also a plain 6-digit message. If the code is valid, save the client's Telegram `chat_id` on the client record and confirm in the client's language.
3. Rate-limit code attempts per chat. Never accept a code twice.
4. Laravel Scheduler sends reminders at the client's chosen time: training of the day, meal times, water, monthly height measurement.

Payload the prototype shows for the bot server:

```json
{
  "phone": "+998 90 123 45 67",
  "code": "123456",
  "lang": "uz",
  "level": "mid",
  "reminders": { "workout": true, "meals": true, "water": false, "measure": true },
  "time": "18:00"
}
```

The bot username is not decided yet. Do not invent one.

## Languages

- Uzbek is written in Latin script. Russian needs three plural forms (1 month, 2 months, 5 months), Uzbek and English do not need the same logic.
- Keep key parity: every key in `lang-en.js` must exist in `lang-uz.js` and `lang-ru.js`. In Laravel use `lang/{en,uz,ru}`.
- Uzbek and Russian text was written by Claude and must be reviewed by a native speaker.

## Privacy

Height, weight and age of children are sensitive. Store the minimum, never write phone numbers or measurements to plain logs, ask for consent at registration, and let the client delete their data.

## Themes

Tokens live in `resources/css/app.css` (`:root` plus `[data-theme=...]`, each with a dark-mode variant). `App\Support\Theme` picks the scheme and the layouts put it on `<body data-theme>`. Status colours (good, warn, bad) and level colours (Easy blue, Mid amber, Strong red) are shared by every scheme.

Built:
- `claude` (main, default): guests, landing page, admins. Warm cream background `#FAF9F5`, clay-orange accent `#B9583A` (dark: `#D97757` on `#1F1E1D`).
- `green`: signed-in male clients. The prototype's palette, accent `#0E7C61`.
- `pink`: signed-in female clients. Pink, accent `#C93A75` (dark: `#F59AC0`).
- The registration wizard switches scheme live when a gender is picked.
- `.claude/hackatone-health-school.html`: school-themed prototype (notebook paper, chalkboard green, animated school scene, floating doodles; motion off with reduced motion). Not in the Laravel app.

Ideas (not built, ask before starting):
- School theme in the Laravel app (for example for clients under 18), reusing the scene and doodles from the school prototype.
- Let the client pick a scheme in Account instead of it following gender.

When adding a scheme: define light and dark tokens, keep text on `--accent` at 4.5:1 contrast or more, and add a case to `tests/Feature/ThemeTest.php`.

## Working conventions

- UI is Livewire 4 (class components in `app/Livewire`, views in `resources/views/livewire`) with Tailwind 4, mobile first. Layouts: `layouts::app` (public) and `layouts::panel` (user and admin panels, menu by role).
- Validation goes in Livewire Form objects (`app/Livewire/Forms`) for Livewire pages, and in Form Requests for plain controllers (login). Calculations (WHO, energy, achievements, calendar) go in plain service classes under `app/Services` with unit tests (`tests/Unit/Health`).
- Roles: `users.role` is `admin` or `user`. A user's health profile is `clients` (one per user). An account is confirmed by the email link or by linking Telegram (`confirmed` middleware).
- All UI text goes through `t()` / `tn()` with keys in `lang/{en,uz,ru}.json`; keep key parity (a unit test checks it).
- Databases: SQLite in development and tests, MySQL in production (assume MySQL 5.7 strict mode). Every migration must run on both: no NOT NULL `timestamp` without a default (use `dateTime`), keep indexed strings short, no `enum` columns. Edit the existing `create_` migrations; do not add `add_`/`alter_` ones until the app is live. The read-only `database-engineer` agent reviews schema changes.
- Hosting is shared hosting: no queue worker. Bot messages arrive by webhook (`POST /telegram/webhook`, secret header checked). Cron (`schedule:run` every minute) only sends user reminders (`reminders:send`). `telegram:poll` is a manual tool for local development without HTTPS. Bot settings are edited by admins in the panel (stored encrypted in `settings`).
- Run `php artisan test` before finishing a task. Check `composer.json` and `package.json` for the real scripts, this file does not list them.
- Activity examples are GIF files on the `uploads` disk only (no external links, no YouTube). AI video (Hugging Face Inference Providers, token in admin Settings) comes back as an MP4 draft; on Publish the admin's browser converts it to a small GIF (`resources/js/gif-convert.js`, gifenc: 320 px, 8 fps, max 4 s, one 64-colour palette, shrinks to fit the upload limit) because shared hosting has no ffmpeg. Nothing reaches clients without an admin watching and publishing it; keep the safety line in `VideoGenerator::SAFETY` and the download host allowlist.
- Ask before adding packages or changing the database schema.
