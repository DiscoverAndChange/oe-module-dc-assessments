<?php

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Utils;

use Http\Message\Encoding\GzipEncodeStream;
use Nyholm\Psr7\Factory\Psr17Factory;
use OpenApi\Util;
use OpenEMR\Common\Acl\AccessDeniedException;
use OpenEMR\Common\Logging\SystemLogger;
use OpenEMR\FHIR\Config\ServerConfig;
use OpenEMR\FHIR\R4\FHIRDomainResource\FHIRQuestionnaireResponse;
use OpenEMR\FHIR\R4\FHIRElement\FHIRCanonical;
use OpenEMR\FHIR\R4\FHIRElement\FHIRId;
use OpenEMR\FHIR\R4\FHIRElement\FHIRQuestionnaireResponseStatus;
use OpenEMR\FHIR\R4\FHIRElement\FHIRString;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\ErrorCode;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\ErrorCodeStatus;
use OpenEMR\RestControllers\RestControllerHelper;
use OpenEMR\Services\FHIR\UtilsService;
use OpenEMR\Validators\ProcessingResult;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class RestUtils
{
    const FHIR_PREFER_HEADER_RETURN_VALUES = ['minimal', 'representation', 'OperationOutcome'];

    public static function getErrorResponse(SystemLogger $logger, \Exception $error): ResponseInterface
    {
        $logger->errorLogCaller($error->getMessage(), ['trace' => $error->getTraceAsString()]);

        if ($error instanceof AccessDeniedException) {
            $message = xlt("Access Denied");
        } else {
            $message = $error->getMessage();
        }
        $codeAsString = ErrorCode::getErrorStringForErrorCode($error->getCode() ?? ErrorCode::SYSTEM_ERROR);
        $err = [
            '_code' => $codeAsString
            ,'_message' => $message || xl("An error has occurred see code for details")
            ,'error' => $error->getMessage() || xl("An error has occurred see code for details") // make sure to be backwards compatible for old code.
        ];

        $statusCode = ErrorCodeStatus::getStatusForErrorCode($error->getCode() ?? ErrorCode::SYSTEM_ERROR);

        // don't reveal details of the error to the frontend.
        if ($statusCode >= 500) {
            $err['error'] = $err['_message'] = xl("A system error occurred.  Please try again or contact support.");
        }
        $psrFactory = new Psr17Factory();
        return $psrFactory->createResponse($statusCode)->withBody($psrFactory->createStream(json_encode($err)));
    }
    public static function returnAccessDeniedResponse(SystemLogger $logger, $logMessage): ResponseInterface
    {
        $logger->errorLogCaller($logMessage);
        $psrFactory = new Psr17Factory();
        return $psrFactory->createResponse(401)->withBody($psrFactory->createStream(json_encode(['error' => xlt('Access Denied')])));
    }
    public static function getNotFoundResponse(): ResponseInterface
    {
        $psrFactory = new Psr17Factory();
        return $psrFactory->createResponse(404)->withBody($psrFactory->createStream(json_encode(['error' => xlt('Not Found')])));
    }


    public static function returnTextResponse($text): ResponseInterface
    {
        $psrFactory = new Psr17Factory();
        $response = $psrFactory->createResponse(200);
        $stream = $psrFactory->createStream($text);
        $response = $response->withHeader('Content-Type', 'text/html')
            ->withBody($stream);
        return $response;
    }

    public static function returnSingleObjectResponse($object): ResponseInterface
    {
        $psrFactory = new Psr17Factory();
        // should we gzip this?

        $response = $psrFactory->createResponse(200);
        $stream = $psrFactory->createStream(json_encode($object));
        $stream->rewind(); // have to rewind the stream.
        $encodedStream = new GzipEncodeStream($stream);
        $response = $response->withAddedHeader('Content-Encoding', 'gzip')
            ->withHeader('Content-Type', 'application/json')
            ->withBody($encodedStream);
        return $response;
    }

    public static function getEmptyResponse(): ResponseInterface
    {
        $psrFactory = new Psr17Factory();
        return $psrFactory->createResponse(200)->withBody($psrFactory->createStream(json_encode([])));
    }

    public static function getResponseForProcessingResult(ProcessingResult $processingResult)
    {
        $httpResponseBody = [];
        if (!$processingResult->isValid()) {
            $status = 400;
            $httpResponseBody["validationErrors"] = $processingResult->getValidationMessages();
        } elseif (count($processingResult->getData()) <= 0) {
            return RestUtils::getNotFoundResponse();
        } elseif ($processingResult->hasInternalErrors()) {
            $httpResponseBody["internalErrors"] = $processingResult->getInternalErrors();
        } else {
            return RestUtils::returnSingleObjectResponse($processingResult->getData()[0]);
        }
        $psrFactory = new Psr17Factory();
        return $psrFactory->createResponse($status)->withBody($psrFactory->createStream(json_encode($httpResponseBody)));
    }

    public static function getFhirCreateResponseForProcessingResult(string $resourceType, ProcessingResult $result)
    {
        $psrFactory = new Psr17Factory();
        if (!$result->isValid()) {
            $status = 400;
            if ($result->hasInternalErrors()) {
                $status = 500;
                $detailedText = implode(" ", $result->getInternalErrors());
                $operationOutcome = UtilsService::createOperationOutcomeResource('fatal', 'transient', $detailedText);
            } else {
                // TODO: if we had more details or more specific codes we could provide better values here
                $detailedText = implode(" ", $result->getValidationMessages());
                $operationOutcome = UtilsService::createOperationOutcomeResource('error', 'processing', $detailedText);
            }
            return $psrFactory->createResponse($status)->withBody($psrFactory->createStream(json_encode($operationOutcome)));
        }
        $data = $result->getData();
        $id = array_shift($data);

        $response = $psrFactory->createResponse(201);
        return self::addFhirLocationHeader($response, $resourceType, $id);
    }

    public static function addFhirLocationHeader(ResponseInterface $response, string $resourceType, int|string $id)
    {
        $serverConfig = new ServerConfig();
        $url = $serverConfig->getFhirUrl() . "/" . $resourceType . "/" . $id;
        return $response->withHeader("Location", $url);
    }

    public static function getFhirOperationOutcomeSuccessResponse(string $resourceType, int|string $id)
    {
        $operationOutcome = UtilsService::createOperationOutcomeSuccess($resourceType, $id);
        $psrFactory = new Psr17Factory();
        return $psrFactory->createResponse(200)->withBody($psrFactory->createStream(json_encode($operationOutcome)));
    }

    /**
     * @param string $preferHeaderValue
     * @return 'minimal'|'representation'|'OperationOutcome'
     */
    public static function getReturnTypeFromPrefer(string $preferHeaderValue): string
    {
        $parts = explode("=", $preferHeaderValue);
        $prefer = end($parts);
        if (!in_array($prefer, self::FHIR_PREFER_HEADER_RETURN_VALUES)) {
            return 'minimal';
        }
        return $prefer;
    }

    // if we decided to bring in the Symfony DenormalizerInterface and PhpDocExtractor into the main
    // project we can bring this method into core.
    public static function hydrateFhirObjectFromJson(string $requestBody, string $objectType): object
    {
        $phpDocExtractor = new PhpDocExtractor();
        $encoders = [new JsonEncoder()];
        $normalizers = [
            new ArrayDenormalizer()
            , new FhirObjectDenormalizer()
            , new ObjectNormalizer(null, null, null, $phpDocExtractor)
        ];
        $serializer = new Serializer($normalizers, $encoders);

        $hydratedObject = $serializer->deserialize($requestBody, $objectType, 'json');
        return $hydratedObject;
    }

    public static function emitResponse(ResponseInterface $response): void
    {
        if (headers_sent()) {
            throw new \RuntimeException('Headers already sent.');
        }
        $statusLine = sprintf(
            'HTTP/%s %s %s',
            $response->getProtocolVersion(),
            $response->getStatusCode(),
            $response->getReasonPhrase()
        );
        header($statusLine, true);
        foreach ($response->getHeaders() as $name => $values) {
            $responseHeader = sprintf('%s: %s', $name, $response->getHeaderLine($name));
            header($responseHeader, false);
        }
        echo $response->getBody();
    }
}
