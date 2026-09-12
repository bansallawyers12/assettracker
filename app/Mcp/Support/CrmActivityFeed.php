<?php

namespace App\Mcp\Support;

use App\Models\Document;
use App\Models\Invoice;
use App\Models\MailMessage;
use App\Models\Note;
use App\Models\Reminder;
use App\Models\Transaction;
use Illuminate\Support\Str;

final class CrmActivityFeed
{
    /**
     * @param  list<int>  $entityIds
     * @param  list<int>  $assetIds
     * @return list<array<string, mixed>>
     */
    public function for(array $entityIds, array $assetIds = [], ?string $email = null, int $limit = 20): array
    {
        $entityIds = array_values(array_unique(array_filter($entityIds)));
        $assetIds = array_values(array_unique(array_filter($assetIds)));
        $limit = max(1, min($limit, 50));

        if ($entityIds === [] && $assetIds === [] && blank($email)) {
            return [];
        }

        $scoped = $entityIds !== [] || $assetIds !== [];

        $items = array_merge(
            $scoped ? $this->notes($entityIds, $assetIds, $limit) : [],
            $scoped ? $this->reminders($entityIds, $assetIds, $limit) : [],
            $this->mail($entityIds, $assetIds, $email, $limit),
            $scoped ? $this->documents($entityIds, $assetIds, $limit) : [],
            $scoped ? $this->transactions($entityIds, $assetIds, $limit) : [],
            $scoped ? $this->invoices($entityIds, $assetIds, $limit) : [],
        );

        usort($items, function (array $left, array $right): int {
            return strcmp((string) $right['occurred_at'], (string) $left['occurred_at']);
        });

        return array_slice($items, 0, $limit);
    }

    /**
     * @param  list<int>  $entityIds
     * @param  list<int>  $assetIds
     * @return list<array<string, mixed>>
     */
    private function notes(array $entityIds, array $assetIds, int $limit): array
    {
        return Note::query()
            ->with('user:id,name')
            ->where(function ($query) use ($entityIds, $assetIds): void {
                if ($entityIds !== []) {
                    $query->orWhereIn('business_entity_id', $entityIds);
                }
                if ($assetIds !== []) {
                    $query->orWhereIn('asset_id', $assetIds);
                }
            })
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (Note $note): array => [
                'kind' => 'note',
                'id' => $note->id,
                'occurred_at' => optional($note->created_at)?->toIso8601String(),
                'summary' => Str::limit((string) $note->content, 240),
                'user' => $note->user?->name,
            ])
            ->all();
    }

    /**
     * @param  list<int>  $entityIds
     * @param  list<int>  $assetIds
     * @return list<array<string, mixed>>
     */
    private function reminders(array $entityIds, array $assetIds, int $limit): array
    {
        return Reminder::query()
            ->where(function ($query) use ($entityIds, $assetIds): void {
                if ($entityIds !== []) {
                    $query->orWhereIn('business_entity_id', $entityIds);
                }
                if ($assetIds !== []) {
                    $query->orWhereIn('asset_id', $assetIds);
                }
            })
            ->orderByDesc('next_due_date')
            ->limit($limit)
            ->get()
            ->map(fn (Reminder $reminder): array => [
                'kind' => 'reminder',
                'id' => $reminder->id,
                'occurred_at' => optional($reminder->next_due_date ?? $reminder->reminder_date)?->toIso8601String(),
                'summary' => $reminder->title,
                'is_completed' => $reminder->is_completed,
                'is_overdue' => ! $reminder->is_completed && $reminder->next_due_date && $reminder->next_due_date->lt(now()),
            ])
            ->all();
    }

    /**
     * @param  list<int>  $entityIds
     * @param  list<int>  $assetIds
     * @return list<array<string, mixed>>
     */
    private function mail(array $entityIds, array $assetIds, ?string $email, int $limit): array
    {
        if ($entityIds === [] && $assetIds === [] && blank($email)) {
            return [];
        }

        return MailMessage::query()
            ->where(function ($query) use ($entityIds, $assetIds, $email): void {
                if ($entityIds !== []) {
                    $query->orWhereHas('businessEntities', fn ($entities) => $entities->whereIn('business_entities.id', $entityIds));
                }
                if ($assetIds !== []) {
                    $query->orWhereHas('assets', fn ($assets) => $assets->whereIn('assets.id', $assetIds));
                }
                if (filled($email)) {
                    $normalized = mb_strtolower(trim($email));
                    $query->orWhereRaw('LOWER(sender_email) = ?', [$normalized])
                        ->orWhereRaw('LOWER(COALESCE(recipients, \'\')) LIKE ? ESCAPE \'\\\'', ['%'.addcslashes($normalized, '%_\\').'%']);
                }
            })
            ->orderByDesc('sent_date')
            ->limit($limit)
            ->get()
            ->map(fn (MailMessage $message): array => [
                'kind' => 'mail',
                'id' => $message->id,
                'occurred_at' => optional($message->sent_date)?->toIso8601String(),
                'summary' => $message->subject ?: '(no subject)',
                'from' => trim(($message->sender_name ?? '').' <'.($message->sender_email ?? '').'>'),
            ])
            ->all();
    }

    /**
     * @param  list<int>  $entityIds
     * @param  list<int>  $assetIds
     * @return list<array<string, mixed>>
     */
    private function documents(array $entityIds, array $assetIds, int $limit): array
    {
        return Document::query()
            ->where(function ($query) use ($entityIds, $assetIds): void {
                if ($entityIds !== []) {
                    $query->orWhereIn('business_entity_id', $entityIds);
                }
                if ($assetIds !== []) {
                    $query->orWhereIn('asset_id', $assetIds);
                }
            })
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (Document $document): array => [
                'kind' => 'document',
                'id' => $document->id,
                'occurred_at' => optional($document->created_at)?->toIso8601String(),
                'summary' => $document->file_name ?: $document->description,
            ])
            ->all();
    }

    /**
     * @param  list<int>  $entityIds
     * @param  list<int>  $assetIds
     * @return list<array<string, mixed>>
     */
    private function transactions(array $entityIds, array $assetIds, int $limit): array
    {
        return Transaction::query()
            ->where(function ($query) use ($entityIds, $assetIds): void {
                if ($entityIds !== []) {
                    $query->orWhereIn('business_entity_id', $entityIds);
                }
                if ($assetIds !== []) {
                    $query->orWhereIn('asset_id', $assetIds);
                }
            })
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(fn (Transaction $transaction): array => [
                'kind' => 'transaction',
                'id' => $transaction->id,
                'occurred_at' => optional($transaction->date ?? $transaction->created_at)?->toIso8601String(),
                'summary' => trim(($transaction->transaction_type ?? 'transaction').' '.$transaction->amount.' '.($transaction->description ?? '')),
                'payment_status' => $transaction->payment_status,
            ])
            ->all();
    }

    /**
     * @param  list<int>  $entityIds
     * @param  list<int>  $assetIds
     * @return list<array<string, mixed>>
     */
    private function invoices(array $entityIds, array $assetIds, int $limit): array
    {
        return Invoice::query()
            ->where(function ($query) use ($entityIds, $assetIds): void {
                if ($entityIds !== []) {
                    $query->orWhereIn('business_entity_id', $entityIds);
                }
                if ($assetIds !== []) {
                    $query->orWhereIn('asset_id', $assetIds);
                }
            })
            ->orderByDesc('issue_date')
            ->limit($limit)
            ->get()
            ->map(fn (Invoice $invoice): array => [
                'kind' => 'invoice',
                'id' => $invoice->id,
                'occurred_at' => optional($invoice->issue_date)?->toIso8601String(),
                'summary' => trim(($invoice->invoice_number ?? 'Invoice').' '.$invoice->customer_name.' '.$invoice->total_amount),
                'status' => $invoice->status,
            ])
            ->all();
    }
}
