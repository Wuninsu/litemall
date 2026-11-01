<?php

namespace Tests\Unit;

use App\Support\Money;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    /**
     * @return array<string, array{string, int}>
     */
    public static function validAmounts(): array
    {
        return [
            'whole cedis' => ['1200', 120000],
            'one decimal' => ['12.5', 1250],
            'two decimals' => ['12.50', 1250],
            'pesewas only' => ['0.05', 5],
            'zero' => ['0', 0],
            'thousands commas' => ['1,200.50', 120050],
            'millions commas' => ['2,500,000', 250000000],
            'surrounding spaces' => ['  45.00 ', 4500],
            'symbol prefix' => ['GH₵ 3,450', 345000],
            'GHS prefix' => ['GHS 10', 1000],
            'leading zeros' => ['007.10', 710],
            'largest allowed' => ['9999999999.99', Money::MAX_PESEWAS],
        ];
    }

    #[DataProvider('validAmounts')]
    public function test_it_parses_cedi_amounts_to_pesewas(string $input, int $expected): void
    {
        $this->assertSame($expected, Money::parse($input));
        $this->assertTrue(Money::isValid($input));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidAmounts(): array
    {
        return [
            'empty' => [''],
            'letters' => ['abc'],
            'three decimals' => ['1.005'],
            'negative' => ['-5'],
            'two dots' => ['1.2.3'],
            'misplaced comma' => ['12,00'],
            'too large' => ['10000000000'],
            'scientific notation' => ['1e3'],
        ];
    }

    #[DataProvider('invalidAmounts')]
    public function test_it_rejects_invalid_amounts(string $input): void
    {
        $this->assertFalse(Money::isValid($input));

        $this->expectException(InvalidArgumentException::class);
        Money::parse($input);
    }

    public function test_parsing_avoids_floating_point_errors(): void
    {
        // 0.1 + 0.2 style inputs that go wrong with floats.
        $this->assertSame(30, Money::parse('0.10') + Money::parse('0.20'));
        $this->assertSame(1999, Money::parse('19.99'));
        $this->assertSame(115, Money::parse('1.15'));
    }

    public function test_it_formats_pesewas_as_cedis(): void
    {
        $this->assertSame('GH₵ 1,200.50', Money::format(120050));
        $this->assertSame('GH₵ 0.05', Money::format(5));
        $this->assertSame('GH₵ 0.00', Money::format(0));
        $this->assertSame('-GH₵ 12.30', Money::format(-1230));
        $this->assertSame('3,450.00', Money::format(345000, withSymbol: false));
    }

    public function test_input_form_round_trips(): void
    {
        $this->assertSame('1200.50', Money::toInput(120050));
        $this->assertSame('0.05', Money::toInput(5));
        $this->assertSame(120050, Money::parse(Money::toInput(120050)));
    }
}
