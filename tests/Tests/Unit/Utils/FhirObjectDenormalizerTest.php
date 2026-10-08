<?php

declare(strict_types=1);

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Tests\Unit\Utils;

use OpenEMR\FHIR\R4\FHIRElement\FHIRCanonical;
use OpenEMR\FHIR\R4\FHIRElement\FHIRCode;
use OpenEMR\FHIR\R4\FHIRElement\FHIRDecimal;
use OpenEMR\FHIR\R4\FHIRElement\FHIRId;
use OpenEMR\FHIR\R4\FHIRElement\FHIRQuestionnaireResponseStatus;
use OpenEMR\FHIR\R4\FHIRElement\FHIRString;
use OpenEMR\FHIR\R4\FHIRElement\FHIRTaskStatus;
use OpenEMR\FHIR\R4\FHIRElement\FHIRUri;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Utils\FhirObjectDenormalizer;
use PHPUnit\Framework\TestCase;

/**
 * Pure-unit (no DB) characterization tests for FhirObjectDenormalizer.
 *
 * The denormalizer maps a raw scalar into one of three families of FHIR R4
 * primitive wrappers: "string" classes constructed directly from the scalar,
 * "array value" classes constructed from ['value' => $data], and "number"
 * classes constructed from a (float) cast. Anything it does not support is
 * returned unchanged. No OpenEMR runtime/DB is required.
 */
class FhirObjectDenormalizerTest extends TestCase
{
    private FhirObjectDenormalizer $denormalizer;

    protected function setUp(): void
    {
        $this->denormalizer = new FhirObjectDenormalizer();
    }

    public function testSupportsDenormalizationReturnsTrueForEverySupportedClass(): void
    {
        foreach (FhirObjectDenormalizer::SUPPORTED_CLASSES as $class) {
            $this->assertTrue(
                $this->denormalizer->supportsDenormalization('anything', $class),
                "expected $class to be supported"
            );
        }
    }

    public function testSupportsDenormalizationReturnsFalseForUnsupportedTypes(): void
    {
        $this->assertFalse($this->denormalizer->supportsDenormalization('x', \stdClass::class));
        $this->assertFalse($this->denormalizer->supportsDenormalization('x', 'string'));
        $this->assertFalse($this->denormalizer->supportsDenormalization('x', \DateTime::class));
    }

    public function testGetSupportedTypesMapsEverySupportedClassToTrue(): void
    {
        $types = $this->denormalizer->getSupportedTypes(null);

        $this->assertCount(count(FhirObjectDenormalizer::SUPPORTED_CLASSES), $types);
        foreach (FhirObjectDenormalizer::SUPPORTED_CLASSES as $class) {
            $this->assertArrayHasKey($class, $types);
            $this->assertTrue($types[$class]);
        }
        $this->assertArrayNotHasKey(\stdClass::class, $types, 'unsupported classes are not advertised');
    }

    public function testGetSupportedTypesIgnoresFormatArgument(): void
    {
        $this->assertSame(
            $this->denormalizer->getSupportedTypes(null),
            $this->denormalizer->getSupportedTypes('json')
        );
    }

    public function testDenormalizeStringClassWrapsScalarAsValue(): void
    {
        /** @var FHIRString $result */
        $result = $this->denormalizer->denormalize('hello', FHIRString::class);
        $this->assertInstanceOf(FHIRString::class, $result);
        $this->assertSame('hello', $result->getValue());
    }

    public function testDenormalizeEachStringFamilyClass(): void
    {
        $cases = [
            FHIRCanonical::class => 'http://example.com/Questionnaire/1',
            FHIRId::class => 'abc-123',
            FHIRUri::class => 'urn:uuid:1234',
            FHIRCode::class => 'final',
        ];
        foreach ($cases as $class => $value) {
            $result = $this->denormalizer->denormalize($value, $class);
            $this->assertInstanceOf($class, $result);
            $this->assertSame($value, $result->getValue(), "value round-trips for $class");
        }
    }

    public function testDenormalizeArrayValueClassWrapsDataUnderValueKey(): void
    {
        /** @var FHIRTaskStatus $task */
        $task = $this->denormalizer->denormalize('completed', FHIRTaskStatus::class);
        $this->assertInstanceOf(FHIRTaskStatus::class, $task);
        $this->assertSame('completed', $task->getValue());

        /** @var FHIRQuestionnaireResponseStatus $qr */
        $qr = $this->denormalizer->denormalize('in-progress', FHIRQuestionnaireResponseStatus::class);
        $this->assertInstanceOf(FHIRQuestionnaireResponseStatus::class, $qr);
        $this->assertSame('in-progress', $qr->getValue());
    }

    public function testDenormalizeNumberClassCastsToFloat(): void
    {
        /** @var FHIRDecimal $fromString */
        $fromString = $this->denormalizer->denormalize('3.5', FHIRDecimal::class);
        $this->assertInstanceOf(FHIRDecimal::class, $fromString);
        $this->assertSame(3.5, $fromString->getValue());

        /** @var FHIRDecimal $fromInt */
        $fromInt = $this->denormalizer->denormalize(7, FHIRDecimal::class);
        $this->assertSame(7.0, $fromInt->getValue(), 'integer input is cast to float');
    }

    public function testDenormalizeUnsupportedTypeReturnsDataUnchanged(): void
    {
        $this->assertSame('passthrough', $this->denormalizer->denormalize('passthrough', \stdClass::class));
        $this->assertSame(42, $this->denormalizer->denormalize(42, \DateTime::class));

        $payload = ['a' => 1];
        $this->assertSame($payload, $this->denormalizer->denormalize($payload, 'not-a-class'));
    }
}
