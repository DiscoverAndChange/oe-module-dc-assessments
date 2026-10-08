<?php

declare(strict_types=1);

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Tests\Unit\Services;

use OpenEMR\Common\Logging\SystemLogger;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\ErrorCode;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\HTTPResponseUtils;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\SystemError;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

/**
 * Pure-unit (no DB) characterization tests for
 * HTTPResponseUtils::jsonErrorResponseHandler().
 *
 * The handler turns a SystemError into a PSR-7 JSON error response: it maps the
 * error code to an HTTP status and a stable string code, falls back for a zero
 * code / empty message, and masks the client-facing message for 5xx errors. It
 * only logs (SystemLogger) and builds a Nyholm response - no DB/network.
 */
class HTTPResponseUtilsTest extends TestCase
{
    /** @return array<string,mixed> */
    private function handle(SystemError $error): array
    {
        $response = HTTPResponseUtils::jsonErrorResponseHandler(new SystemLogger(), $error);
        $this->assertInstanceOf(ResponseInterface::class, $response);
        /** @var array<string,mixed> $decoded */
        $decoded = json_decode((string) $response->getBody(), true);
        $decoded['__status'] = $response->getStatusCode();
        return $decoded;
    }

    public function testClientErrorKeepsOriginalMessageAndMapsStatus(): void
    {
        $body = $this->handle(new SystemError(ErrorCode::VALIDATION_FAILED, 'name is required'));

        $this->assertSame(400, $body['__status']);
        $this->assertSame('VALIDATION_FAILED', $body['_code']);
        $this->assertSame('name is required', $body['_message']);
        $this->assertSame('name is required', $body['error'], 'backwards-compatible error key mirrors _message');
    }

    public function testNotFoundErrorMapsTo404(): void
    {
        $body = $this->handle(new SystemError(ErrorCode::RECORD_NOT_FOUND, 'no such record'));

        $this->assertSame(404, $body['__status']);
        $this->assertSame('RECORD_NOT_FOUND', $body['_code']);
        $this->assertSame('no such record', $body['_message']);
    }

    public function testServerErrorMasksMessage(): void
    {
        $body = $this->handle(new SystemError(ErrorCode::SYSTEM_ERROR, 'raw sql dump with credentials'));

        $this->assertSame(500, $body['__status']);
        $this->assertSame('SYSTEM_ERROR', $body['_code']);
        $this->assertStringContainsStringIgnoringCase('system error occurred', (string) $body['_message']);
        $this->assertStringNotContainsString('credentials', (string) $body['error']);
        $this->assertStringNotContainsString('raw sql dump', (string) $body['_message']);
    }

    public function testZeroCodeFallsBackToSystemError(): void
    {
        // getCode() of 0 is falsy, so the handler substitutes ErrorCode::SYSTEM_ERROR.
        $body = $this->handle(new SystemError(0, 'boom'));

        $this->assertSame(500, $body['__status']);
        $this->assertSame('SYSTEM_ERROR', $body['_code']);
    }

    public function testEmptyMessageFallsBackToGenericText(): void
    {
        $body = $this->handle(new SystemError(ErrorCode::RECORD_NOT_FOUND, ''));

        $this->assertSame(404, $body['__status']);
        $this->assertSame('RECORD_NOT_FOUND', $body['_code']);
        $this->assertSame('An error has occurred see code for details', $body['_message']);
        $this->assertSame('An error has occurred see code for details', $body['error']);
    }
}
