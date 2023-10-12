<?php

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Utils;

use OpenEMR\FHIR\R4\FHIRElement\FHIRCanonical;
use OpenEMR\FHIR\R4\FHIRElement\FHIRCode;
use OpenEMR\FHIR\R4\FHIRElement\FHIRDecimal;
use OpenEMR\FHIR\R4\FHIRElement\FHIRId;
use OpenEMR\FHIR\R4\FHIRElement\FHIRQuestionnaireResponseStatus;
use OpenEMR\FHIR\R4\FHIRElement\FHIRString;
use OpenEMR\FHIR\R4\FHIRElement\FHIRTaskStatus;
use OpenEMR\FHIR\R4\FHIRElement\FHIRUri;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class FhirObjectDenormalizer implements DenormalizerInterface
{
    const SUPPORTED_CLASSES = [FHIRCanonical::class, FHIRString::class, FHIRId::class, FHIRQuestionnaireResponseStatus::class
        ,FHIRUri::class, FHIRCode::class, FHIRTaskStatus::class, FHIRDecimal::class];
    const STRING_CLASSES = [FHIRCanonical::class, FHIRString::class, FHIRId::class
        , FHIRUri::class, FHIRCode::class];

    const NUMBER_CLASSES = [FHIRDecimal::class];

    const ARRAY_VALUE_CLASSES = [FHIRTaskStatus::class, FHIRQuestionnaireResponseStatus::class];
    public function denormalize($data, string $type, string $format = null, array $context = [])
    {
        $pos = array_search($type, self::STRING_CLASSES);
        if ($pos !== false) {
            $clazz = self::STRING_CLASSES[$pos];
            return new $clazz((string)$data);
        }
        $arrayPos = array_search($type, self::ARRAY_VALUE_CLASSES);
        if ($arrayPos !== false) {
            $clazz = self::ARRAY_VALUE_CLASSES[$arrayPos];
            return new $clazz(['value' => (string)$data]);
        }
        $numberPos = array_search($type, self::NUMBER_CLASSES);
        if ($numberPos !== false) {
            $clazz = self::NUMBER_CLASSES[$numberPos];
            return new $clazz((float)$data);
        }
        return $data;
    }

    public function supportsDenormalization($data, string $type, string $format = null)
    {
        if (in_array($type, self::SUPPORTED_CLASSES) !== false) {
            return true;
        }
        return false;
    }
}
