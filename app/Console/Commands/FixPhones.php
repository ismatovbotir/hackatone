<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Support\Phone;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * One-off repair: numbers saved before local formats ("0 90...", "8 90...") were understood are rewritten to
 * 998XXXXXXXXX so they match what Telegram sends. Shows only masked numbers. Dry run unless --apply.
 */
#[Signature('hh:fix-phones {--apply : Save the changes (default only shows them)}')]
#[Description('Rewrite saved phone numbers to the full 998... form so Telegram matching works')]
class FixPhones extends Command
{
    public function handle(): int
    {
        $changes = 0;

        foreach (Client::query()->cursor() as $client) {
            $fixed = Phone::normalize($client->phone);
            if ($fixed === null || $fixed === $client->phone) {
                continue;
            }

            if (Client::where('phone', $fixed)->whereKeyNot($client->id)->exists()) {
                $this->warn("Client #{$client->id}: ".Phone::mask($fixed).' already belongs to another client, skipped.');

                continue;
            }

            $this->line("Client #{$client->id}: ".Phone::mask($client->phone).' -> '.Phone::mask($fixed));
            if ($this->option('apply')) {
                $client->forceFill(['phone' => $fixed])->save();
            }
            $changes++;
        }

        $this->info($changes === 0 ? 'All phone numbers are fine.' : ($this->option('apply') ? "Fixed {$changes}." : "{$changes} to fix. Run again with --apply."));

        return self::SUCCESS;
    }
}
