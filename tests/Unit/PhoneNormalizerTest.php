<?php

namespace Tests\Unit;

use App\Services\Crm\PhoneNormalizer;
use PHPUnit\Framework\TestCase;

class PhoneNormalizerTest extends TestCase
{
    /** @dataProvider phoneProvider */
    public function test_normalizes_to_international_digits(string $input, string $expected): void
    {
        $this->assertSame($expected, PhoneNormalizer::normalize($input));
    }

    public static function phoneProvider(): array
    {
        return [
            'egypt local' => ['01012345678', '201012345678'],
            'egypt local with spaces' => ['010 1234 5678', '201012345678'],
            'egypt with plus' => ['+201012345678', '201012345678'],
            'egypt with 00' => ['00201012345678', '201012345678'],
            'egypt 011 prefix' => ['01112345678', '201112345678'],
            'egypt 012 prefix' => ['01212345678', '201212345678'],
            'egypt 015 prefix' => ['01512345678', '201512345678'],
            'saudi local' => ['0512345678', '966512345678'],
            'saudi with plus' => ['+966512345678', '966512345678'],
            'saudi with dashes' => ['05-1234-5678', '966512345678'],
            'already international other' => ['971501234567', '971501234567'],
            'garbage stays digits' => ['12ab34', '1234'],
            'empty' => ['', ''],
        ];
    }

    public function test_null_returns_empty(): void
    {
        $this->assertSame('', PhoneNormalizer::normalize(null));
    }

    public function test_is_likely_valid(): void
    {
        $this->assertTrue(PhoneNormalizer::isLikelyValid('01012345678'));
        $this->assertTrue(PhoneNormalizer::isLikelyValid('+966512345678'));
        $this->assertFalse(PhoneNormalizer::isLikelyValid('1234'));
        $this->assertFalse(PhoneNormalizer::isLikelyValid(''));
        $this->assertFalse(PhoneNormalizer::isLikelyValid(null));
    }

    public function test_match_forms_covers_local_and_international(): void
    {
        $forms = PhoneNormalizer::matchForms('01012345678');
        $this->assertContains('01012345678', $forms);
        $this->assertContains('201012345678', $forms);

        $forms = PhoneNormalizer::matchForms('+201012345678');
        $this->assertContains('201012345678', $forms);
        $this->assertContains('01012345678', $forms); // re-derived local form

        $forms = PhoneNormalizer::matchForms('966512345678');
        $this->assertContains('0512345678', $forms);
    }
}
