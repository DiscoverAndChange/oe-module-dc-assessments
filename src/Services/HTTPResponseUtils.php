<?php

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Services;

use Nyholm\Psr7\Factory\Psr17Factory;
use OpenEMR\Common\Logging\SystemLogger;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\ErrorCode;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\ErrorCodeStatus;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\SystemError;
use Psr\Http\Message\ResponseInterface;

class HTTPResponseUtils
{
    public static function jsonErrorResponseHandler(SystemLogger $logger, SystemError $error): ResponseInterface
    {
        $psrFactory = new Psr17Factory();

        // SystemError is a Throwable, so capture the message and stack trace.
        $logger->error($error->getMessage(), ['class' => get_class($error), 'stack' => $error->getTraceAsString()]);

        $code = $error->getCode() ?: ErrorCode::SYSTEM_ERROR;
        $statusCode = ErrorCodeStatus::getStatusForErrorCode($code);

        $err = [
            '_code' => ErrorCode::getErrorStringForErrorCode($code),
            '_message' => $error->getMessage() ?: 'An error has occurred see code for details',
            'error' => $error->getMessage() ?: 'An error has occurred see code for details', // make sure to be backwards compatible for old code.
        ];

        // don't reveal details of the error to the frontend.
        if ($statusCode >= 500) {
            $err['error'] = $err['_message'] = 'A system error occurred. Please try again or contact support.';
        }

        return $psrFactory->createResponse($statusCode)->withBody($psrFactory->createStream((string) json_encode($err)));
    }
}
