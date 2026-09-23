<?php

namespace Tests\Unit;

use App\Support\TrackingCode;
use PHPUnit\Framework\TestCase;

class TrackingCodeTest extends TestCase
{
    public function test_strips_usps_420_zip_prefix_and_trailing_digits(): void
    {
        $this->assertSame(
            '9400111899562537862167',
            TrackingCode::canonical('42033142940011189956253786216799')
        );
    }

    public function test_keeps_usps_core_when_already_clean(): void
    {
        $this->assertSame(
            '9400111899562537862167',
            TrackingCode::canonical('9400111899562537862167')
        );
    }

    public function test_keeps_warehouse_ups_and_amazon_codes(): void
    {
        $this->assertSame('010015', TrackingCode::canonical('010015'));
        $this->assertSame('1Z999AA10123456784', TrackingCode::canonical('1Z999AA10123456784'));
        $this->assertSame('TBA334243264207', TrackingCode::canonical('TBA334243264207'));
        $this->assertSame('SPXMIA010062601540002015', TrackingCode::canonical('spxmia010062601540002015'));
    }

    public function test_keeps_international_usps_start(): void
    {
        $this->assertSame('EC123456789US', TrackingCode::canonical('EC123456789USXX'));
    }

    public function test_matches_dirty_scan_to_clean_tracking(): void
    {
        $this->assertTrue(TrackingCode::matches(
            '9400111899562537862167',
            '42033142940011189956253786216799'
        ));
        $this->assertTrue(TrackingCode::matches(
            '420331429400111899562537862167',
            '9400111899562537862167'
        ));
        $this->assertFalse(TrackingCode::matches(
            '9400111899562537862167',
            '9400111899562537862199'
        ));
    }
}
