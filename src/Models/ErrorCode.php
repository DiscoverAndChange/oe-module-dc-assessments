<?php

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Models;

class ErrorCode
{
    // 400 errors
    const INVALID_REQUEST = 4000;
    const INVALID_BILLING_CARD = 4001;
    const DUP_ENTRY = 4002;
    const VALIDATE_DATA_MISSING = 4003;
    const VALIDATION_FAILED = 4004;

    // 401 errors
    const AUTHORIZATION_REQUIRED = 4011;
    const MUST_AUTHENTICATE = 4012;

    // 402 errors
    const ASSESSMENT_QUOTA_REACHED = 4021;

    // 404 errors
    const RECORD_NOT_FOUND = 4041;

    // 500 errors
    const SYSTEM_ERROR = 5001;
    const RECORD_CREATE_FAILED = 5002;

    const codeMap = array(
        ErrorCode::DUP_ENTRY => "INVALID_REQUEST",
        ErrorCode::INVALID_REQUEST => "INVALID_REQUEST",
        ErrorCode::VALIDATE_DATA_MISSING => "VALIDATE_DATA_MISSING",
        ErrorCode::VALIDATION_FAILED => "VALIDATION_FAILED",
        ErrorCode::INVALID_BILLING_CARD => "INVALID_BILLING_CARD",
        ErrorCode::AUTHORIZATION_REQUIRED => "AUTHORIZATION_REQUIRED",
        ErrorCode::MUST_AUTHENTICATE => "MUST_AUTHENTICATE",
        ErrorCode::ASSESSMENT_QUOTA_REACHED => "ASSESSMENT_QUOTA_REACHED",
        ErrorCode::RECORD_NOT_FOUND => "RECORD_NOT_FOUND",
        ErrorCode::SYSTEM_ERROR => "SYSTEM_ERROR",
        ErrorCode::RECORD_CREATE_FAILED => "RECORD_CREATE_FAILED"
    );

    public static function getErrorStringForErrorCode($code): string
    {

        $status = self::codeMap[ErrorCode::SYSTEM_ERROR];
        if (array_key_exists($code, ErrorCodeStatus::codeMap)) {
            $status = ErrorCode::codeMap[$code];
        }

        return $status;
    }
}
