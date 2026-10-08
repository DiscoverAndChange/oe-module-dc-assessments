<?php

declare(strict_types=1);

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Tests;

use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\ErrorCode;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Pure-unit characterization tests for ErrorCode::getErrorStringForErrorCode,
 * the numeric-code -> string-name mapping.
 */
class ErrorCodeTest extends TestCase
{
    /**
     * @return array<string, array{int, string}>
     */
    public static function mappedCodeProvider(): array
    {
        return [
            // DUP_ENTRY intentionally maps to the INVALID_REQUEST string.
            'DUP_ENTRY -> INVALID_REQUEST' => [ErrorCode::DUP_ENTRY, 'INVALID_REQUEST'],
            'INVALID_REQUEST' => [ErrorCode::INVALID_REQUEST, 'INVALID_REQUEST'],
            'VALIDATE_DATA_MISSING' => [ErrorCode::VALIDATE_DATA_MISSING, 'VALIDATE_DATA_MISSING'],
            'VALIDATION_FAILED' => [ErrorCode::VALIDATION_FAILED, 'VALIDATION_FAILED'],
            'INVALID_BILLING_CARD' => [ErrorCode::INVALID_BILLING_CARD, 'INVALID_BILLING_CARD'],
            'AUTHORIZATION_REQUIRED' => [ErrorCode::AUTHORIZATION_REQUIRED, 'AUTHORIZATION_REQUIRED'],
            'MUST_AUTHENTICATE' => [ErrorCode::MUST_AUTHENTICATE, 'MUST_AUTHENTICATE'],
            'ASSESSMENT_QUOTA_REACHED' => [ErrorCode::ASSESSMENT_QUOTA_REACHED, 'ASSESSMENT_QUOTA_REACHED'],
            'RECORD_NOT_FOUND' => [ErrorCode::RECORD_NOT_FOUND, 'RECORD_NOT_FOUND'],
            'SYSTEM_ERROR' => [ErrorCode::SYSTEM_ERROR, 'SYSTEM_ERROR'],
            'RECORD_CREATE_FAILED' => [ErrorCode::RECORD_CREATE_FAILED, 'RECORD_CREATE_FAILED'],
        ];
    }

    #[DataProvider('mappedCodeProvider')]
    public function testGetErrorStringForKnownCode(int $code, string $expected): void
    {
        $this->assertSame($expected, ErrorCode::getErrorStringForErrorCode($code));
    }

    public function testUnknownCodeFallsBackToSystemError(): void
    {
        $this->assertSame('SYSTEM_ERROR', ErrorCode::getErrorStringForErrorCode(9999));
    }

    public function testZeroCodeFallsBackToSystemError(): void
    {
        $this->assertSame('SYSTEM_ERROR', ErrorCode::getErrorStringForErrorCode(0));
    }
}
