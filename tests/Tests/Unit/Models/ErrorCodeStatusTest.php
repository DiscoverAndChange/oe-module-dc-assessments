<?php

declare(strict_types=1);

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Tests;

use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\ErrorCode;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\ErrorCodeStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Pure-unit characterization tests for ErrorCodeStatus::getStatusForErrorCode,
 * the numeric-code -> HTTP-status mapping.
 */
class ErrorCodeStatusTest extends TestCase
{
    /**
     * @return array<string, array{int, int}>
     */
    public static function mappedStatusProvider(): array
    {
        return [
            'DUP_ENTRY -> 400' => [ErrorCode::DUP_ENTRY, 400],
            'INVALID_REQUEST -> 400' => [ErrorCode::INVALID_REQUEST, 400],
            'VALIDATE_DATA_MISSING -> 400' => [ErrorCode::VALIDATE_DATA_MISSING, 400],
            'VALIDATION_FAILED -> 400' => [ErrorCode::VALIDATION_FAILED, 400],
            'INVALID_BILLING_CARD -> 400' => [ErrorCode::INVALID_BILLING_CARD, 400],
            'AUTHORIZATION_REQUIRED -> 401' => [ErrorCode::AUTHORIZATION_REQUIRED, 401],
            'MUST_AUTHENTICATE -> 401' => [ErrorCode::MUST_AUTHENTICATE, 401],
            'ASSESSMENT_QUOTA_REACHED -> 402' => [ErrorCode::ASSESSMENT_QUOTA_REACHED, 402],
            'RECORD_NOT_FOUND -> 404' => [ErrorCode::RECORD_NOT_FOUND, 404],
            'SYSTEM_ERROR -> 500' => [ErrorCode::SYSTEM_ERROR, 500],
            'RECORD_CREATE_FAILED -> 500' => [ErrorCode::RECORD_CREATE_FAILED, 500],
        ];
    }

    #[DataProvider('mappedStatusProvider')]
    public function testGetStatusForKnownCode(int $code, int $expected): void
    {
        $this->assertSame($expected, ErrorCodeStatus::getStatusForErrorCode($code));
    }

    public function testUnknownCodeFallsBackTo500(): void
    {
        $this->assertSame(500, ErrorCodeStatus::getStatusForErrorCode(9999));
    }

    public function testZeroCodeFallsBackTo500(): void
    {
        $this->assertSame(500, ErrorCodeStatus::getStatusForErrorCode(0));
    }
}
