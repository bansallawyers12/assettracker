<?php

namespace App\Services;

use App\Mail\InvoiceMail;
use App\Models\Invoice;
use App\Models\User;
use App\Support\InvoiceEmailMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;

class UpcomingRentInvoiceMailer
{
    public function __construct(
        private InvoicePdfRenderer $pdf,
        private SentMailRecorder $recorder,
    ) {}

    /**
     * Email approved rent invoices once, starting two days before the due date.
     * A missed morning is retried through the due date.
     *
     * @return array{sent: int, skipped: int}
     */
    public function sendDueSoon(?Carbon $today = null): array
    {
        $today = ($today ?? Carbon::now('Australia/Melbourne'))->copy()->startOfDay();
        $dueOn = $today->copy()->addDays(Invoice::EMAIL_DAYS_BEFORE_DUE)->toDateString();

        $invoices = Invoice::query()
            ->whereNotNull('lease_id')
            ->whereIn('status', ['approved', 'partial'])
            ->whereNull('last_invoice_emailed_at')
            ->whereDate('due_date', '>=', $today->toDateString())
            ->whereDate('due_date', '<=', $dueOn)
            ->with(['lease.tenant', 'businessEntity', 'lines.chartOfAccount', 'asset'])
            ->get();

        $sent = 0;
        $skipped = 0;
        $mailboxUsers = $this->mailboxUsers();

        foreach ($invoices as $invoice) {
            $entity = $invoice->businessEntity;
            $email = trim((string) ($invoice->lease?->tenant?->email ?? ''));

            if ($entity === null || $email === '' || $invoice->amountDue() <= 0) {
                $skipped++;

                continue;
            }

            $subject = 'Invoice '.$invoice->invoice_number;
            $message = InvoiceEmailMessage::body($invoice, $entity);
            $replyTo = $entity->registeredEmailIsPlaceholder()
                ? null
                : trim((string) $entity->registered_email);

            try {
                Mail::to($email)->send(new InvoiceMail(
                    invoice: $invoice,
                    messageBody: $message,
                    pdfFilename: $this->pdf->filename($invoice),
                    pdfContents: $this->pdf->binary($entity, $invoice),
                    replyToAddress: $replyTo !== '' ? $replyTo : null,
                    subjectLine: $subject,
                ));
            } catch (\Throwable $e) {
                report($e);
                $skipped++;

                continue;
            }

            $invoice->update([
                'last_invoice_emailed_at' => now(),
            ]);

            foreach ($mailboxUsers as $mailboxUser) {
                $this->recorder->record(
                    userId: (int) $mailboxUser->id,
                    senderName: $entity->legal_name,
                    subject: $subject,
                    recipients: $email,
                    message: $message,
                    entity: $entity,
                    assetId: $invoice->asset_id ? (int) $invoice->asset_id : null,
                );
            }

            $sent++;
        }

        return [
            'sent' => $sent,
            'skipped' => $skipped,
        ];
    }

    /**
     * Sent mail is stored on the primary administrator's mailbox.
     * When that account is absent, active staff and administrators are used.
     *
     * @return Collection<int, User>
     */
    private function mailboxUsers(): Collection
    {
        $users = User::query()->where('is_active', true)->orderBy('id')->get();
        $admins = $users->filter(fn (User $user): bool => $user->isPrimaryAdministrator())->values();

        if ($admins->isNotEmpty()) {
            return $admins;
        }

        return $users->filter(fn (User $user): bool => $user->canMutatePortfolio())->values();
    }
}
