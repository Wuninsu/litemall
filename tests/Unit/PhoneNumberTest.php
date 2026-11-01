<?php

namespace Tests\Unit;

use App\Support\PhoneNumber;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PhoneNumberTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function sameNumberWrittenDifferently(): array
    {
        return [
            'local' => ['0244123456'],
            'local with spaces' => ['024 412 3456'],
            'local with dashes' => ['024-412-3456'],
            'international' => ['+233244123456'],
            'international with spaces' => ['+233 24 412 3456'],
            'without plus' => ['233244123456'],
            'with 00 prefix' => ['00233244123456'],
            'with brackets' => ['(024) 412 3456'],
        ];
    }

    #[DataProvider('sameNumberWrittenDifferently')]
    public function test_it_normalises_ghana_numbers_to_e164(string $input): void
    {
        $this->assertSame('+233244123456', PhoneNumber::normalise($input));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidNumbers(): array
    {
        return [
            'too short' => ['024412345'],
            'too long' => ['02441234567'],
            'letters' => ['024ABC3456'],
            'foreign country code' => ['+2348031234567'],
            'national number starting with 1' => ['0144123456'],
            'empty' => [''],
        ];
    }

    #[DataProvider('invalidNumbers')]
    public function test_it_rejects_invalid_numbers(string $input): void
    {
        $this->assertNull(PhoneNumber::normalise($input));
    }

    public function test_it_accepts_fixed_line_numbers(): void
    {
        $this->assertSame('+233302123456', PhoneNumber::normalise('030 212 3456'));
    }

    public function test_it_formats_stored_numbers_for_display(): void
    {
        $this->assertSame('024 412 3456', PhoneNumber::format('+233244123456'));
        $this->assertSame('not-a-number', PhoneNumber::format('not-a-number'));
    }

    public function test_search_fragments_match_how_numbers_are_stored(): void
    {
        $this->assertSame('24412', PhoneNumber::searchFragment('0244 12'));
        $this->assertSame('244', PhoneNumber::searchFragment('+233 244'));
        $this->assertSame('4123456', PhoneNumber::searchFragment('412-3456'));
        $this->assertNull(PhoneNumber::searchFragment('02'));
        $this->assertNull(PhoneNumber::searchFragment('Kwame'));
    }
}
