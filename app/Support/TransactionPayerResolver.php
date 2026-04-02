<?php

namespace App\Support;

use App\Models\BusinessEntity;
use App\Models\EntityPerson;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TransactionPayerResolver
{
    /**
     * @return array{companies: list<array{value: string, label: string}>, directors: list<array{value: string, label: string}>}
     */
    public static function payerOptionsForUserId(int $userId): array
    {
        $companies = BusinessEntity::query()
            ->where('user_id', $userId)
            ->orderBy('legal_name')
            ->get()
            ->map(fn (BusinessEntity $e) => [
                'value' => 'be:'.$e->id,
                'label' => $e->legal_name,
            ])
            ->all();

        $entityIds = BusinessEntity::query()
            ->where('user_id', $userId)
            ->pluck('id');

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

            return $e ? $e->legal_name : $stored;
        }
        if (preg_match('/^ep:(\d+)$/', $stored, $m)) {
            $ep = EntityPerson::query()->with(['person', 'businessEntity'])->find($m[1]);
            if (! $ep) {
                return $stored;
            }
            $p = $ep->person;
            $name = $p ? trim($p->first_name.' '.$p->last_name) : '';
            $entityLabel = $ep->businessEntity?->legal_name ?? '';
            if ($name === '') {
                return $entityLabel !== '' ? $entityLabel : $stored;
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
        $sel = $request->input('paid_by_select');
        if ($sel === null || $sel === '') {
            return null;
        }
        if ($sel === 'other') {
            $t = trim((string) $request->input('paid_by_other', ''));

            return $t === '' ? null : $t;
        }

        return is_string($sel) ? $sel : null;
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
            if (! $ep || $ep->role !== 'Director' || $ep->role_status !== 'Active') {
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
