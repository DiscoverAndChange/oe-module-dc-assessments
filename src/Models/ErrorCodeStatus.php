<?php

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Models;

class ErrorCodeStatus
{
    const codeMap = array(
        ErrorCode::DUP_ENTRY => 400,
        ErrorCode::INVALID_REQUEST => 400,
        ErrorCode::VALIDATE_DATA_MISSING => 400,
        ErrorCode::VALIDATION_FAILED => 400,
        ErrorCode::INVALID_BILLING_CARD => 400,
        ErrorCode::AUTHORIZATION_REQUIRED => 401,
        ErrorCode::MUST_AUTHENTICATE => 401,
        ErrorCode::ASSESSMENT_QUOTA_REACHED => 402,
        ErrorCode::RECORD_NOT_FOUND => 404,
        ErrorCode::SYSTEM_ERROR => 500,
        ErrorCode::RECORD_CREATE_FAILED => 500
    );

    public static function getStatusForErrorCode($code): int
    {

        $status = self::codeMap[ErrorCode::SYSTEM_ERROR];
        if (array_key_exists($code, ErrorCodeStatus::codeMap)) {
            $status = ErrorCodeStatus::codeMap[$code];
        }

        return $status;
    }
}
