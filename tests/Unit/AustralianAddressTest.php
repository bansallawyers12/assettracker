<?php

namespace Tests\Unit;

use App\Support\AustralianAddress;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AustralianAddressTest extends TestCase
{
    #[DataProvider('addressProvider')]
    public function test_format_for_display(string $input, string $expected): void
    {
        $this->assertSame($expected, AustralianAddress::formatForDisplay($input));
    }

    public function test_empty_and_null(): void
    {
        $this->assertSame('', AustralianAddress::formatForDisplay(null));
        $this->assertSame('', AustralianAddress::formatForDisplay(''));
        $this->assertSame('', AustralianAddress::formatForDisplay('   '));
        $this->assertSame('', AustralianAddress::formatForDisplay('Australia'));
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function addressProvider(): array
    {
        return [
            'google style with country' => [
                '6 Lisbon St, Glen Waverley VIC 3150, Australia',
                '6 Lisbon St, Glen Waverley VIC 3150',
            ],
            'all caps no commas' => [
                '6 LISBON STREET GLEN WAVERLEY VIC 3150 Australia',
                '6 Lisbon St, Glen Waverley, VIC 3150',
            ],
            'lowercase partial' => [
                '8/278 collins street, melbourne',
                '8/278 Collins St, Melbourne',
            ],
            'circuit abbreviation' => [
                '40 STOCKMANS CIRCUIT PAKENHAM VIC 3810',
                '40 Stockmans Cct, Pakenham, VIC 3810',
            ],
            'already tidy' => [
                '706/343 Little Collins St, Melbourne VIC 3000',
                '706/343 Little Collins St, Melbourne VIC 3000',
            ],
        ];
    }
}
