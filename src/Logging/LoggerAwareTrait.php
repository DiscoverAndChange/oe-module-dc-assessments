<?php

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Logging;

use OpenEMR\Common\Logging\SystemLogger;
use Symfony\Contracts\Service\Attribute\Required;

trait LoggerAwareTrait
{
    protected $logger;

    #[Required]
    public function setLogger(SystemLogger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @return SystemLogger
     */
    public function getLogger()
    {
        return $this->logger;
    }
}
