<?php

use App\Models\Asset;
use App\Models\BankAccount;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('assets', 'rent_bsb')) {
            return;
        }

        Asset::query()
            ->where(function ($query) {
                $query->whereNotNull('rent_bsb')
                    ->orWhereNotNull('rent_account_number');
            })
            ->with('businessEntity')
            ->chunkById(100, function ($assets) {
                foreach ($assets as $asset) {
                    $bsb = BankAccount::normalizeBsb($asset->rent_bsb);
                    $accountNumber = trim((string) ($asset->rent_account_number ?? ''));

                    if ($bsb === null && $accountNumber === '') {
                        continue;
                    }

                    if ($bsb === null || strlen($bsb) !== 6) {
                        continue;
                    }

                    $userId = $asset->user_id
                        ?? $asset->businessEntity?->user_id;

                    if (! $userId) {
                        continue;
                    }

                    $bankAccount = BankAccount::query()
                        ->whereNull('business_entity_id')
                        ->where('user_id', $userId)
                        ->where('account_purpose', BankAccount::PURPOSE_LOAN_REPAYMENT)
                        ->where('bsb', $bsb)
                        ->get()
                        ->first(function (BankAccount $account) use ($accountNumber) {
                            return $account->account_number === $accountNumber;
                        });

                    if (! $bankAccount) {
                        $bankAccount = BankAccount::create([
                            'business_entity_id' => null,
                            'user_id' => $userId,
                            'bank_name' => 'Migrated',
                            'bsb' => $bsb,
                            'account_number' => $accountNumber !== '' ? $accountNumber : 'unknown',
                            'account_name' => 'Loan repayment ('.$bsb.')',
                            'account_purpose' => BankAccount::PURPOSE_LOAN_REPAYMENT,
                        ]);
                    }

                    DB::table('asset_bank_account')->updateOrInsert(
                        [
                            'asset_id' => $asset->id,
                            'role' => 'loan_repayment',
                        ],
                        [
                            'bank_account_id' => $bankAccount->id,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                }
            });

        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn(['rent_bsb', 'rent_account_number']);
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->string('rent_bsb', 10)->nullable()->after('equity_required');
            $table->string('rent_account_number', 20)->nullable()->after('rent_bsb');
        });

        $links = DB::table('asset_bank_account')
            ->where('role', 'loan_repayment')
            ->get();

        foreach ($links as $link) {
            $bankAccount = BankAccount::find($link->bank_account_id);
            if (! $bankAccount) {
                continue;
            }

            Asset::where('id', $link->asset_id)->update([
                'rent_bsb' => BankAccount::formatBsb($bankAccount->bsb),
                'rent_account_number' => $bankAccount->account_number,
            ]);
        }
    }
};
