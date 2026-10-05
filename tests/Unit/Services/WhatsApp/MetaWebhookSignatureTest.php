<?php

namespace Tests\Unit\Services\WhatsApp;

use App\Services\WhatsApp\MetaWebhookSignature as S;
use PHPUnit\Framework\TestCase;

class MetaWebhookSignatureTest extends TestCase
{
    private const SECRET = 'app-secret-123';
    private const BODY   = '{"object":"whatsapp_business_account","entry":[{"id":"1"}]}';

    private function sign(string $body, string $secret = self::SECRET): string
    {
        return 'sha256=' . hash_hmac('sha256', $body, $secret);
    }

    /** @test */
    public function a_correctly_signed_body_is_accepted(): void
    {
        $this->assertTrue(S::isValid(self::BODY, $this->sign(self::BODY), self::SECRET));
    }

    /** @test */
    public function a_tampered_body_is_rejected(): void
    {
        $this->assertFalse(S::isValid(self::BODY . ' ', $this->sign(self::BODY), self::SECRET));
        $this->assertFalse(S::isValid(str_replace('"1"', '"2"', self::BODY), $this->sign(self::BODY), self::SECRET));
    }

    /** @test */
    public function a_signature_made_with_another_secret_is_rejected(): void
    {
        $this->assertFalse(S::isValid(self::BODY, $this->sign(self::BODY, 'someone-elses-secret'), self::SECRET));
    }

    /** @test */
    public function a_missing_or_malformed_header_is_rejected(): void
    {
        $hex = hash_hmac('sha256', self::BODY, self::SECRET);

        $this->assertFalse(S::isValid(self::BODY, null, self::SECRET));
        $this->assertFalse(S::isValid(self::BODY, '', self::SECRET));
        $this->assertFalse(S::isValid(self::BODY, $hex, self::SECRET));                 // no "sha256=" prefix
        $this->assertFalse(S::isValid(self::BODY, 'sha1=' . $hex, self::SECRET));       // wrong algorithm label
        $this->assertFalse(S::isValid(self::BODY, 'sha256=', self::SECRET));
    }

    /** @test */
    public function without_a_configured_secret_nothing_can_validate(): void
    {
        // an empty secret must never turn into "everything passes"
        $this->assertFalse(S::isValid(self::BODY, $this->sign(self::BODY, ''), ''));
    }
}
