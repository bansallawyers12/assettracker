<?php

use Tests\TestCase;

uses(TestCase::class);

it('seeds dedicated expense accounts for formerly coarse transaction types', function () {
    $seeder = file_get_contents(database_path('seeders/ChartOfAccountSeeder.php'));
    $posting = file_get_contents(app_path('Services/TransactionPostingService.php'));

    expect($seeder)->toContain("['5200', 'Marketing & Advertising'")
        ->and($seeder)->toContain("['5210', 'Travel Expenses'")
        ->and($seeder)->toContain("['5220', 'Rent & Office Utilities'")
        ->and($seeder)->toContain("['5230', 'Cost of Goods Sold'")
        ->and($seeder)->toContain("['5240', 'Related Party Expenses'")
        ->and($seeder)->toContain("['7510', 'Loan Fees'")
        ->and($seeder)->toContain("['1150', 'Deposits Paid'")
        ->and($posting)->toContain("'marketing_advertising' => \$this->findByName('Marketing & Advertising')")
        ->and($posting)->toContain("->findAccount('5200')")
        ->and($posting)->toContain("'travel_expenses' => \$this->findByName('Travel Expenses')")
        ->and($posting)->toContain("->findAccount('5210')")
        ->and($posting)->toContain("'rent_utilities' => \$this->findByName('Rent & Office Utilities')")
        ->and($posting)->toContain("->findAccount('5220')")
        ->and($posting)->toContain("'cogs' => \$this->findByName('Cost of Goods Sold')")
        ->and($posting)->toContain("->findAccount('5230')")
        ->and($posting)->toContain("'rent_to_related_party' => \$this->findByName('Related Party Expenses')")
        ->and($posting)->toContain("->findAccount('5240')")
        ->and($posting)->toContain("'loan_fees' => \$this->findByName('Loan Fees')")
        ->and($posting)->toContain("->findAccount('7510')")
        ->and($posting)->toContain("->findAccount('5125')");
});
