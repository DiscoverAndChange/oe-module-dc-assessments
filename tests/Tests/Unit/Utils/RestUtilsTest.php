<?php

declare(strict_types=1);

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Tests\Unit\Utils;

use OpenEMR\Common\Logging\SystemLogger;
use OpenEMR\FHIR\R4\FHIRElement\FHIRString;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\ErrorCode;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Utils\RestUtils;
use OpenEMR\Validators\ProcessingResult;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

/**
 * Pure-unit (no DB, no network) characterization tests for RestUtils.
 *
 * These exercise the PSR-7 response builders that need only the Nyholm
 * Psr17Factory plus OpenEMR's translation helpers (available once globals are
 * bootstrapped) and in-memory ProcessingResult objects. The ServerConfig /
 * UtilsService-backed builders (addFhirLocationHeader, getFhirCreate...,
 * getFhirOperationOutcomeSuccessResponse) and emitResponse() are deferred -
 * they read FHIR server config / emit real HTTP headers and are not pure units.
 */
class RestUtilsTest extends TestCase
{
    /** @return array<string,mixed> */
    private function decodeBody(ResponseInterface $response): array
    {
        /** @var array<string,mixed> $decoded */
        $decoded = json_decode((string) $response->getBody(), true);
        return $decoded;
    }

    public function testGetReturnTypeFromPreferParsesHeaderValue(): void
    {
        $this->assertSame('representation', RestUtils::getReturnTypeFromPrefer('return=representation'));
        $this->assertSame('minimal', RestUtils::getReturnTypeFromPrefer('return=minimal'));
        $this->assertSame('OperationOutcome', RestUtils::getReturnTypeFromPrefer('return=OperationOutcome'));
    }

    public function testGetReturnTypeFromPreferAcceptsBareValueWithoutEquals(): void
    {
        $this->assertSame('representation', RestUtils::getReturnTypeFromPrefer('representation'));
    }

    public function testGetReturnTypeFromPreferDefaultsToMinimalForUnknownValues(): void
    {
        $this->assertSame('minimal', RestUtils::getReturnTypeFromPrefer('return=bogus'));
        $this->assertSame('minimal', RestUtils::getReturnTypeFromPrefer('garbage'));
        $this->assertSame('minimal', RestUtils::getReturnTypeFromPrefer(''));
    }

    public function testReturnSingleObjectResponseEmitsJson(): void
    {
        $response = RestUtils::returnSingleObjectResponse(['id' => 5, 'name' => 'Assess']);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));
        $this->assertSame(['id' => 5, 'name' => 'Assess'], $this->decodeBody($response));
    }

    public function testReturnTextResponseEmitsHtml(): void
    {
        $response = RestUtils::returnTextResponse('<p>hi</p>');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('text/html', $response->getHeaderLine('Content-Type'));
        $this->assertSame('<p>hi</p>', (string) $response->getBody());
    }

    public function testGetEmptyResponseReturnsEmptyJsonArray(): void
    {
        $response = RestUtils::getEmptyResponse();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('[]', (string) $response->getBody());
    }

    public function testGetNotFoundResponseReturns404(): void
    {
        $response = RestUtils::getNotFoundResponse();

        $this->assertSame(404, $response->getStatusCode());
        $this->assertArrayHasKey('error', $this->decodeBody($response));
    }

    public function testGetAccessDeniedResponseReturns403(): void
    {
        $response = RestUtils::getAccessDeniedResponse();

        $this->assertSame(403, $response->getStatusCode());
        $this->assertArrayHasKey('error', $this->decodeBody($response));
    }

    public function testReturnAccessDeniedResponseReturns401(): void
    {
        $response = RestUtils::returnAccessDeniedResponse(new SystemLogger(), 'denied for test');

        $this->assertSame(401, $response->getStatusCode());
        $this->assertArrayHasKey('error', $this->decodeBody($response));
    }

    public function testGetServerErrorResponseReturns500AndDoesNotLeakDetail(): void
    {
        $response = RestUtils::getServerErrorResponse(new \RuntimeException('secret stacktrace detail'));
        $body = $this->decodeBody($response);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertStringContainsStringIgnoringCase('system error occurred', (string) $body['error']);
        $this->assertStringNotContainsString('secret stacktrace', (string) $body['error']);
    }

    public function testGetErrorResponseMapsErrorCodeToStatusAndCodeString(): void
    {
        $error = new \Exception('record missing', ErrorCode::RECORD_NOT_FOUND);
        $response = RestUtils::getErrorResponse(new SystemLogger(), $error);
        $body = $this->decodeBody($response);

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame('RECORD_NOT_FOUND', $body['_code']);
    }

    public function testGetErrorResponseMasksDetailForServerErrors(): void
    {
        $error = new \Exception('raw internal db dsn leaked', ErrorCode::SYSTEM_ERROR);
        $response = RestUtils::getErrorResponse(new SystemLogger(), $error);
        $body = $this->decodeBody($response);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame('SYSTEM_ERROR', $body['_code']);
        // 500-level errors replace the client-facing message with a generic one.
        $this->assertStringContainsStringIgnoringCase('system error occurred', (string) $body['_message']);
        $this->assertStringNotContainsString('db dsn leaked', (string) $body['error']);
    }

    public function testGetResponseForProcessingResultReturns400WhenInvalid(): void
    {
        $result = new ProcessingResult();
        $result->setValidationMessages(['name' => 'is required']);

        $response = RestUtils::getResponseForProcessingResult($result);
        $body = $this->decodeBody($response);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame(['name' => 'is required'], $body['validationErrors']);
    }

    public function testGetResponseForProcessingResultReturns404WhenValidButEmpty(): void
    {
        $result = new ProcessingResult(); // valid, no data

        $response = RestUtils::getResponseForProcessingResult($result);

        $this->assertSame(404, $response->getStatusCode());
    }

    public function testGetResponseForProcessingResultReturnsFirstDataRowWhenValid(): void
    {
        $result = new ProcessingResult();
        $result->setData([['id' => 7, 'label' => 'first'], ['id' => 8]]);

        $response = RestUtils::getResponseForProcessingResult($result);
        $body = $this->decodeBody($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['id' => 7, 'label' => 'first'], $body, 'returns the first data element only');
    }

    /**
     * REGRESSION (fixed v0.12.3): the hasInternalErrors() branch never set $status, so
     * createResponse($status) hit an undefined variable. It now returns a 500 with the
     * internalErrors payload.
     */
    public function testGetResponseForProcessingResultReturns500OnInternalErrors(): void
    {
        $result = new ProcessingResult();
        $result->setData([['id' => 7]]); // valid + non-empty so flow reaches the internal-errors branch
        $result->addInternalError('boom');

        $response = RestUtils::getResponseForProcessingResult($result);
        $body = $this->decodeBody($response);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertArrayHasKey('internalErrors', $body);
    }

    public function testHydrateFhirObjectFromJsonUsesTheDenormalizerWiring(): void
    {
        /** @var FHIRString $hydrated */
        $hydrated = RestUtils::hydrateFhirObjectFromJson('"hydrated-value"', FHIRString::class);

        $this->assertInstanceOf(FHIRString::class, $hydrated);
        $this->assertSame('hydrated-value', $hydrated->getValue());
    }
}
