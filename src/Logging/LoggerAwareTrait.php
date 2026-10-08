<?php

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Logging;

use OpenEMR\Common\Logging\SystemLogger;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Service\Attribute\Required;

trait LoggerAwareTrait
{
    // Must match PSR-3 LoggerAwareTrait's property exactly (?LoggerInterface,
    // null default): OpenEMR 8.4.1's FhirServiceBase composes PSR's
    // LoggerAwareTrait, so any incompatible redeclaration here is a fatal.
    protected ?LoggerInterface $logger = null;

    // Signature matches PSR's setLogger(LoggerInterface): void so it is a
    // compatible override on classes that also inherit it from the base.
    // #[Required] keeps Symfony DI autowiring the logger via a setter.
    #[Required]
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function getLogger(): ?LoggerInterface
    {
        return $this->logger;
    }
}
