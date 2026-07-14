<?php

namespace App\Services;

use App\Models\ComplianceDocumentFile;
use App\Models\Reminder;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Creates 30 / 14 / 7 day-before reminders for entity compliance due dates.
 */
class ComplianceReminderService
{
    /** @var list<int> */
    public const OFFSET_DAYS = [30, 14, 7];

    public const CATEGORY = 'compliance';

    public const REMINDER_TYPE = 'compliance_document_file';

    /**
     * @return array{created: int, skipped: int, examined: int}
     */
    public function sync(?int $entityId = null, bool $dryRun = false, ?int $userId = null): array
    {
        $fallbackUserId = $userId ?? Auth::id();
        $today = now()->startOfDay();
        $created = 0;
        $skipped = 0;
        $examined = 0;

        $query = ComplianceDocumentFile::query()
            ->whereNotNull('due_date')
            ->whereNotNull('compliance_document_type_id')
            ->whereHas('yearRecord', function ($q) use ($entityId) {
                $q->whereNull('asset_id');
                if ($entityId !== null) {
                    $q->where('business_entity_id', $entityId);
                }
            })
            ->where(function ($q) {
                $q->whereNull('status')
                    ->orWhereNotIn('status', ['lodged', 'paid']);
            })
            ->with(['type', 'yearRecord.businessEntity']);

        $query->orderBy('id')->chunkById(100, function ($files) use ($today, $fallbackUserId, $dryRun, &$created, &$skipped, &$examined) {
            foreach ($files as $file) {
                $examined++;
                $due = $file->due_date?->copy()->startOfDay();
                $entity = $file->yearRecord?->businessEntity;
                $fyStart = $file->yearRecord?->fy_start_date;

                if ($due === null || $entity === null || $due->lt($today)) {
                    $skipped++;

                    continue;
                }

                if ($fyStart !== null && ! $entity->complianceAppliesForFinancialYear($fyStart)) {
                    $skipped++;

                    continue;
                }

                $ownerId = $fallbackUserId ?? $entity->user_id ?? User::query()->value('id');
                if ($ownerId === null) {
                    $skipped++;

                    continue;
                }

                $label = $file->displayLabel();
                $fyLabel = $file->yearRecord->fy_start_date
                    ? \App\Support\FinancialYear::label($file->yearRecord->fy_start_date)
                    : '';

                foreach (self::OFFSET_DAYS as $offset) {
                    $reminderDate = $due->copy()->subDays($offset);
                    if ($reminderDate->lt($today)) {
                        $skipped++;

                        continue;
                    }

                    $marker = $this->marker($file->id, $offset);

                    $exists = Reminder::query()
                        ->where('reminder_type', self::REMINDER_TYPE)
                        ->where('reminder_id', $file->id)
                        ->where('notes', $marker)
                        ->where('is_completed', false)
                        ->exists();

                    if ($exists) {
                        $skipped++;

                        continue;
                    }

                    if (! $dryRun) {
                        Reminder::query()->create([
                            'title' => sprintf('%s due in %d days', $label, $offset),
                            'content' => sprintf(
                                '%s — %s for %s is due on %s.',
                                $entity->legal_name,
                                $label,
                                $fyLabel,
                                $due->toDateString()
                            ),
                            'reminder_date' => $reminderDate,
                            'next_due_date' => $reminderDate,
                            'repeat_type' => 'none',
                            'business_entity_id' => $entity->id,
                            'category' => self::CATEGORY,
                            'priority' => $offset <= 7 ? 'high' : 'medium',
                            'notes' => $marker,
                            'reminder_type' => self::REMINDER_TYPE,
                            'reminder_id' => $file->id,
                            'user_id' => $ownerId,
                            'status' => 'active',
                            'is_completed' => false,
                        ]);
                    }

                    $created++;
                }
            }
        });

        return compact('created', 'skipped', 'examined');
    }

    public function marker(int $fileId, int $offset): string
    {
        return "compliance_reminder:file:{$fileId}:offset:{$offset}";
    }
}
