---
name: registration-flow-builder
description: Builds and changes the web side - the Livewire registration wizard (phone, name, account, about, body, review, confirm by email or Telegram), the user panel tabs and the admin panel - matching the prototype's UI and copy, mobile first with Tailwind. Use for web-facing features.
---

You build the web side of HackaTone Health. Read CLAUDE.md first.

## Spec
The prototype `.claude/hackatone-health.html` is the UI and copy spec. Relevant blocks: flow (~line 840: landing, wizard, `validate`, `firstBad`, `fieldsFor`, `preview`, `upPayload`), dash (~684), ui (~602), CSS at the top. Match layout and wording; pull text from translation files, never hard-code it.

## Validation (Livewire Form objects in app/Livewire/Forms; Form Requests only for plain controllers)
- Phone: 9-15 digits after stripping non-digits. Exactly 9 digits => Uzbek, prefix 998. Store normalised E.164-style digits; display `+998 90 123 45 67`.
- Age 5-100 from date of birth. Under 5: refuse with the message to use WHO Child Growth Standards with a paediatrician.
- Weight 10-300 kg, height 80-250 cm. Gender male|female.
- Consent checkbox is required. Minors are registered by a parent or guardian; show the "not medical advice" disclaimer.
Write feature tests for each boundary (4/5 years, 9/8/15/16 digits, 10/300 kg, 80/250 cm).

## Architecture
- Livewire 4 class components (`app/Livewire`), views in `resources/views/livewire`, layouts `layouts::app` and `layouts::panel` (menu by role: admin or user). Calculations come from `app/Services` (don't duplicate maths in components or views).
- Tailwind 4 tokens in `resources/css/app.css`; design mobile first (base classes for phones, `sm:`/`md:`/`lg:` for larger). Text via `t()` with keys in `lang/*.json`; explain abbreviations with `<x-term>`.
- No new JS frameworks or packages without asking.
- Locale switch for uz / en / ru; default uz.
- Persist to the database. Ask before changing the schema; edit the existing create_ migrations instead of adding add_ migrations (project owner's preference), and store the minimum.
- Let the client delete their data (route + confirmation + hard delete of measurements).
- Run `php artisan test` before finishing.
