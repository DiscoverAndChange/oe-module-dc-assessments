<?php

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Services;

use Nyholm\Psr7\Factory\Psr17Factory;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\ErrorCode;
use Psr\Http\Message\ResponseInterface;

class HTTPResponseUtils
{
    public static function jsonErrorResponseHandler(SystemLogger $logger, SystemError $error): ResponseInterface
    {
        $psrFactory = new Psr17Factory();

        if ($error instanceof Throwable) {
            // make sure we get the stack trace here, not sure why winston isn't handling this properly.
            $logger->error($error->getMessage(), ['name' => $error->getName(), 'stack' => $error->getTraceAsString()]);
        } else {
            $logger->error($error); // TODO: in some cases we are getting duplicate errors here, but we need to log them for now until we can refactor.
        }

        $err = [
            '_code' => $error->getCode() ?: ErrorCode::SYSTEM_ERROR,
            '_message' => $error->getMessage() ?: 'An error has occurred see code for details',
            'error' => $error->getMessage() ?: 'An error has occurred see code for details', // make sure to be backwards compatible for old code.
        ];

        $err['_code'] = ErrorCode::name($err['_code']);
        $statusCode = ErrorCodeStatus::getStatusForErrorCode($err['_code']);

// don't reveal details of the error to the frontend.
        if ($statusCode >= 500) {
            $err['error'] = $err['_message'] = 'A system error occurred. Please try again or contact support.';
        }

        return $psrFactory->createResponse($statusCode)->withBody(json_encode($err));
    }
}
