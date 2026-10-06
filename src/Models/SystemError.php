<?php

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Models;

/*
 *   Copyright (c) 2021 Discover and Change @author Stephen Nielson <snielson@discoverandchange.com>
 *   All rights reserved.
 *   For License information see LICENSE.md in the root folder of this project.
 */

class SystemError extends \RuntimeException
{
    private int $_code;

    /**
     * @var SystemError[]
     */
    private array $_subErrors;

    public function __construct(int $code, string $message, ?array $subErrors = null)
    {
        parent::__construct($message, $code);
        $this->_code = $code;
        $this->_subErrors = $subErrors ?? [];
    }


    public function code(): int
    {
        return $this->_code;
    }

    public function subErrors(): array
    {
        return $this->_subErrors;
    }
}
