<?php

namespace App\Console\Commands;

use App\Services\UpcomingRentInvoiceMailer;
use Illuminate\Console\Command;

class EmailUpcomingRentInvoices extends Command
{
    protected $signature = 'invoices:email-upcoming-rent';

    protected $description = 'Email approved rent invoices two days before the due date, retrying through the due date if a morning was missed';

    public function handle(UpcomingRentInvoiceMailer $mailer): int
    {
        $result = $mailer->sendDueSoon();

        $this->info(sprintf('Sent %d rent invoice email(s). Skipped %d.', $result['sent'], $result['skipped']));

        return self::SUCCESS;
    }
}
