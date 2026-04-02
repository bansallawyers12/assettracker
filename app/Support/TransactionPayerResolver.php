<?php

namespace App\Support;

use App\Models\BusinessEntity;
use App\Models\EntityPerson;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Resolves transaction "paid by" from UI tokens (be:{entityId}, ep:{entityPersonId}) or free text.
 * Options and validation are scoped to the owning user_id on business_entities.
 */
class TransactionPayerResolver
{
    /**
     * @return array{companies: list<array{value: string, label: string}>, directors: list<array{value: string, label: string}>}
     */
    public static function payerOptionsForUserId(int $userId): array
    {
        $entities = BusinessEntity::query()
            ->where('user_id', $userId)
            ->orderBy('legal_name')
            ->get();

        $companies = $entities
            ->map(fn (BusinessEntity $e) => [
                'value' => 'be:'.$e->id,
                'label' => $e->legal_name,
            ])
            ->values()
            ->all();

        $entityIds = $entities->pluck('id');
        if ($entityIds->isEmpty()) {
            return ['companies' => $companies, 'directors' => []];
        }

        $directors = EntityPerson::query()
            ->whereIn('business_entity_id', $entityIds)
            ->where('role', 'Director')
            ->where('role_status', 'Active')
            ->whereNotNull('person_id')
            ->with(['person', 'businessEntity'])
            ->orderBy('id')
            ->get()
            ->map(function (EntityPerson $ep) {
                $p = $ep->person;
                $name = $p ? trim($p->first_name.' '.$p->last_name) : '';
                $entityLabel = $ep->businessEntity?->legal_name ?? '';
                if ($name === '') {
                    $label = $entityLabel !== '' ? $entityLabel : 'Director #'.$ep->id;
                } else {
                    $label = $entityLabel !== '' ? $name.' — '.$entityLabel : $name;
                }

                return [
                    'value' => 'ep:'.$ep->id,
                    'label' => $label,
                ];
            })
            ->sortBy('label', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();

        return [
            'companies' => $companies,
            'directors' => $directors,
        ];
    }

    public static function paidByLabel(?string $stored): string
    {
        if ($stored === null || $stored === '') {
            return '';
        }
        if (preg_match('/^be:(\d+)$/', $stored, $m)) {
            $e = BusinessEntity::query()->find($m[1]);

            return $e ? $e->legal_name : 'Company (removed)';
        }
        if (preg_match('/^ep:(\d+)$/', $stored, $m)) {
            $ep = EntityPerson::query()->with(['person', 'businessEntity'])->find($m[1]);
            if (! $ep) {
                return 'Director (removed)';
            }
            $p = $ep->person;
            $name = $p ? trim($p->first_name.' '.$p->last_name) : '';
            $entityLabel = $ep->businessEntity?->legal_name ?? '';
            if ($name === '') {
                return $entityLabel !== '' ? $entityLabel : 'Director (removed)';
            }

            return $entityLabel !== '' ? $name.' — '.$entityLabel : $name;
        }

        return $stored;
    }

    /**
     * @return array{select: string, other: string}
     */
    public static function splitStoredForForm(?string $stored): array
    {
        if ($stored === null || $stored === '') {
            return ['select' => '', 'other' => ''];
        }
        if (preg_match('/^(be|ep):\d+$/', $stored)) {
            return ['select' => $stored, 'other' => ''];
        }

        return ['select' => 'other', 'other' => $stored];
    }

    public static function resolveFromRequest(Request $request): ?string
    {
        $raw = $request->input('paid_by_select');
        if ($raw === null || $raw === '') {
            return null;
        }
        if (! is_string($raw)) {
            return null;
        }
        $sel = trim($raw);
        if ($sel === '') {
            return null;
        }
        if ($sel === 'other') {
            $othRaw = $request->input('paid_by_other');
            $t = is_string($othRaw) ? trim($othRaw) : '';

            return $t === '' ? null : $t;
        }

        return $sel;
    }

    public static function assertSelectionAllowed(?string $resolved, int $userId): void
    {
        if ($resolved === null || $resolved === '') {
            return;
        }
        if (! preg_match('/^(be|ep):\d+$/', $resolved)) {
            return;
        }
        if (preg_match('/^be:(\d+)$/', $resolved, $m)) {
            if (! BusinessEntity::query()->where('id', $m[1])->where('user_id', $userId)->exists()) {
                throw ValidationException::withMessages([
                    'paid_by_select' => 'Invalid company selected.',
                ]);
            }

            return;
        }
        if (preg_match('/^ep:(\d+)$/', $resolved, $m)) {
            $ep = EntityPerson::query()->find($m[1]);
            if (! $ep || $ep->role !== 'Director' || $ep->role_status !== 'Active' || $ep->person_id === null) {
                throw ValidationException::withMessages([
                    'paid_by_select' => 'Invalid director selected.',
                ]);
            }
            $be = BusinessEntity::query()->find($ep->business_entity_id);
            if (! $be || (int) $be->user_id !== $userId) {
                throw ValidationException::withMessages([
                    'paid_by_select' => 'Invalid director selected.',
                ]);
            }
        }
    }
}
