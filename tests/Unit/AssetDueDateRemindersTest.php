<?php

namespace Tests\Unit;

use App\Models\Asset;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class AssetDueDateRemindersTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_due_date_reminder_items_includes_overdue_and_upcoming_within_window(): void
    {
        Carbon::setTestNow('2026-06-15');

        $asset = new Asset;
        $asset->setRawAttributes([
            'land_tax_due_date' => '2026-06-10',
            'council_rates_due_date' => '2026-06-20',
            'insurance_due_date' => '2026-08-01',
        ]);

        $items = $asset->dueDateReminderItems(15);

        $this->assertCount(2, $items);
        $this->assertSame('Land Tax', $items[0]['label']);
        $this->assertSame('Council Rates', $items[1]['label']);
    }

    public function test_due_date_reminder_items_excludes_dates_beyond_window(): void
    {
        Carbon::setTestNow('2026-06-15');

        $asset = new Asset;
        $asset->setRawAttributes([
            'land_tax_due_date' => '2026-07-05',
        ]);

        $this->assertCount(0, $asset->dueDateReminderItems(15));
    }

    public function test_due_date_field_names_cover_all_reminder_types(): void
    {
        $this->assertContains('land_tax_due_date', Asset::dueDateFieldNames());
        $this->assertCount(count(Asset::DUE_DATE_REMINDERS), Asset::dueDateFieldNames());
    }
}
