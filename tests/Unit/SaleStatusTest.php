<?php

namespace Tests\Unit;

use App\Enums\SaleStatus;
use PHPUnit\Framework\TestCase;

class SaleStatusTest extends TestCase
{
    public function test_status_follows_the_amount_paid(): void
    {
        $this->assertSame(SaleStatus::Unpaid, SaleStatus::for(100000, 0));
        $this->assertSame(SaleStatus::PartlyPaid, SaleStatus::for(100000, 1));
        $this->assertSame(SaleStatus::PartlyPaid, SaleStatus::for(100000, 99999));
        $this->assertSame(SaleStatus::Paid, SaleStatus::for(100000, 100000));
        $this->assertSame(SaleStatus::Paid, SaleStatus::for(0, 0));
    }

    public function test_labels(): void
    {
        $this->assertSame('Part-paid', SaleStatus::PartlyPaid->label());
    }
}
