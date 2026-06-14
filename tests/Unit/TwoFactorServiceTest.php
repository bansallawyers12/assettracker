<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use PragmaRX\Google2FAQRCode\Google2FA;

class TwoFactorServiceTest extends TestCase
{
    public function test_inline_qr_code_is_generated_as_svg_or_png_data_uri(): void
    {
        $google2fa = new Google2FA;
        $secret = $google2fa->generateSecretKey();
        $qrCode = $google2fa->getQRCodeInline('TestApp', 'user@example.com', $secret, 200);

        $this->assertNotEmpty($qrCode);
        $this->assertMatchesRegularExpression('/(<svg|data:image\/png;base64,)/', $qrCode);
    }
}
