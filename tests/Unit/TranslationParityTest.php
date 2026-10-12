<?php

namespace Tests\Unit;

use App\Support\Tr;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class TranslationParityTest extends TestCase
{
    private function keys(string $locale): array
    {
        return array_keys(json_decode(file_get_contents(__DIR__."/../../lang/$locale.json"), true));
    }

    public function test_every_english_key_exists_in_uzbek_and_russian(): void
    {
        $en = $this->keys('en');
        foreach (['uz', 'ru'] as $locale) {
            $other = $this->keys($locale);
            $this->assertSame([], array_values(array_diff($en, $other)), "missing in $locale");
            $this->assertSame([], array_values(array_diff($other, $en)), "extra in $locale");
        }
    }

    public function test_russian_plural_forms(): void
    {
        $forms = [1 => 0, 2 => 1, 4 => 1, 5 => 2, 11 => 2, 12 => 2, 21 => 0, 22 => 1, 25 => 2, 111 => 2];
        foreach ($forms as $n => $form) {
            $this->assertSame($form, Tr::pluralIndex('ru', $n), "n=$n");
        }
        $this->assertSame(0, Tr::pluralIndex('uz', 5));
        $this->assertSame(1, Tr::pluralIndex('en', 2));
    }

    public function test_uzbek_dates_are_written_in_latin_script(): void
    {
        $this->assertSame('uz_Latn', Tr::dateLocale('uz'));
        $this->assertSame('ru', Tr::dateLocale('ru'));

        $month = Carbon::parse('2026-10-14')->locale(Tr::dateLocale('uz'))->translatedFormat('F');
        $this->assertMatchesRegularExpression('/^[A-Za-z‘\'ʻ]+$/u', $month);
    }
}
