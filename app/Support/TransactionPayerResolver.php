<?php

namespace App\Support;

use App\Models\BusinessEntity;
use App\Models\EntityPerson;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Resolves transaction "paid by" from UI tokens (be:{entityId}, ep:{entityPersonId}) or free text.
 * Payer lists include every business entity and every director appointment (no user/status filters).
 */
class TransactionPayerResolver
{
    /**
     * @return array{companies: list<array{value: string, label: string}>, directors: list<array{value: string, label: string}>}
     */
    public static function payerOptions(): array
    {
        $entities = BusinessEntity::query()
            ->orderBy('legal_name')
            ->get();

        $companies = $entities
            ->map(fn (BusinessEntity $e) => [
                'value' => 'be:'.$e->id,
                'label' => $e->legal_name,
            ])
            ->values()
            ->all();

        $directors = EntityPerson::query()
            ->where('role', 'Director')
            ->with(['person', 'businessEntity', 'trusteeEntity'])
            ->orderBy('id')
            ->get()
            ->map(function (EntityPerson $ep) {
                $entityLabel = $ep->businessEntity?->legal_name ?? '';
                $statusSuffix = $ep->role_status === 'Resigned' ? ' (Resigned)' : '';

                $p = $ep->person;
                if ($ep->person_id && $p) {
                    $name = trim($p->first_name.' '.$p->last_name);
                    $base = $entityLabel !== '' ? $name.' — '.$entityLabel : $name;
                } elseif ($ep->entity_trustee_id && $ep->trusteeEntity) {
                    $trusteeName = $ep->trusteeEntity->legal_name;
                    $base = $entityLabel !== '' ? $trusteeName.' — '.$entityLabel : $trusteeName;
                } else {
                    $base = $entityLabel !== '' ? $entityLabel : 'Director #'.$ep->id;
                }

                return [
                    'value' => 'ep:'.$ep->id,
                    'label' => $base.$statusSuffix,
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
            $ep = EntityPerson::query()->with(['person', 'businessEntity', 'trusteeEntity'])->find($m[1]);
            if (! $ep) {
                return 'Director (removed)';
            }
            $statusSuffix = $ep->role_status === 'Resigned' ? ' (Resigned)' : '';
            $entityLabel = $ep->businessEntity?->legal_name ?? '';
            $p = $ep->person;
            if ($ep->person_id && $p) {
                $name = trim($p->first_name.' '.$p->last_name);
                $base = $entityLabel !== '' ? $name.' — '.$entityLabel : $name;
            } elseif ($ep->entity_trustee_id && $ep->trusteeEntity) {
                $trusteeName = $ep->trusteeEntity->legal_name;
                $base = $entityLabel !== '' ? $trusteeName.' — '.$entityLabel : $trusteeName;
            } else {
                return ($entityLabel !== '' ? $entityLabel : 'Director (removed)').$statusSuffix;
            }

            return $base.$statusSuffix;
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

    public static function assertSelectionAllowed(?string $resolved): void
    {
        if ($resolved === null || $resolved === '') {
            return;
        }
        if (! preg_match('/^(be|ep):\d+$/', $resolved)) {
            return;
        }
        if (preg_match('/^be:(\d+)$/', $resolved, $m)) {
            if (! BusinessEntity::query()->whereKey($m[1])->exists()) {
                throw ValidationException::withMessages([
                    'paid_by_select' => 'Invalid company selected.',
                ]);
            }

            return;
        }
        if (preg_match('/^ep:(\d+)$/', $resolved, $m)) {
            $ep = EntityPerson::query()->find($m[1]);
            if (! $ep || $ep->role !== 'Director') {
                throw ValidationException::withMessages([
                    'paid_by_select' => 'Invalid director selected.',
                ]);
            }
        }
    }
}
