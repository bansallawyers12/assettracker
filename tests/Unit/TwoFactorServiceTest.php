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

        $this->assertSame(32, strlen($secret));
        $this->assertNotEmpty($qrCode);
        $this->assertMatchesRegularExpression('/^data:image\/(?:svg\+xml|png);base64,/', $qrCode);
    }

    public function test_qr_code_image_html_wraps_data_uri_in_img_tag(): void
    {
        $google2fa = new Google2FA;
        $secret = $google2fa->generateSecretKey();
        $dataUri = $google2fa->getQRCodeInline('TestApp', 'user@example.com', $secret, 200);
        $html = '<img src="'.e($dataUri).'" width="200" height="200" alt="QR" class="two-factor-qr-code" decoding="async" />';

        $this->assertStringStartsWith('<img src="data:image/', $html);
        $this->assertStringContainsString('class="two-factor-qr-code"', $html);
        $this->assertStringContainsString('width="200"', $html);
    }
}
