<?php

namespace Tests\Unit;

use App\Services\LogsheetImportService;
use PHPUnit\Framework\TestCase;

class LogsheetImportDateTest extends TestCase
{
    public function test_parse_date_value_handles_supported_values_without_throwing(): void
    {
        $this->assertSame('2026-05-30', LogsheetImportService::parseDateValue(46172));
        $this->assertSame('2026-05-30', LogsheetImportService::parseDateValue(46172.0));
        $this->assertSame('2026-05-30', LogsheetImportService::parseDateValue('46172'));
        $this->assertSame('2026-06-09', LogsheetImportService::parseDateValue('09.06.2026'));
        $this->assertSame('2026-06-09', LogsheetImportService::parseDateValue('09/06/2026'));
        $this->assertSame('2026-06-09', LogsheetImportService::parseDateValue('2026-06-09'));
    }

    public function test_parse_date_value_returns_null_for_empty_zero_and_garbage_values(): void
    {
        $this->assertNull(LogsheetImportService::parseDateValue('00.00.0000'));
        $this->assertNull(LogsheetImportService::parseDateValue(''));
        $this->assertNull(LogsheetImportService::parseDateValue('not-a-date'));
    }
}
