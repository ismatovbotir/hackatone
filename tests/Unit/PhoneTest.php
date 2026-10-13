<?php

namespace Tests\Unit;

use App\Support\Phone;
use PHPUnit\Framework\TestCase;

class PhoneTest extends TestCase
{
    public function test_nine_digits_get_uzbek_prefix(): void
    {
        $this->assertSame('998901234567', Phone::normalize('90 123 45 67'));
        $this->assertSame('+998 90 123 45 67', Phone::format('901234567'));
    }

    public function test_length_limits(): void
    {
        $this->assertNull(Phone::normalize('12345678'));
        $this->assertSame('123456789012345', Phone::normalize('+123456789012345'));
        $this->assertNull(Phone::normalize('1234567890123456'));
    }

    public function test_foreign_numbers_keep_plus_and_digits(): void
    {
        $this->assertSame('+447700900123', Phone::format('+44 7700 900123'));
    }

    public function test_local_uzbek_formats_match_what_telegram_sends(): void
    {
        foreach (['0 90 123 45 67', '8 90 123 45 67', '(090) 123-45-67', '+998 90 123 45 67', '998901234567'] as $typed) {
            $this->assertSame('998901234567', Phone::normalize($typed), $typed);
        }
        $this->assertSame('+998 •• ••• •• 67', Phone::mask('998901234567'));
    }
}
