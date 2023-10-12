<?php

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Models\Import;

class ImportLogEntry
{
    /**
     * @var "AssessmentBlob"|"LibraryAsset"|"AssessmentGroup"|"Report"
     */
    public string $type;


    public string $importResource;

    /**
     * @var "success"|"failure"
     */
    public $importStatus;

    public int $index = 0;

    public ?string $error = null;

    public ?string $successMessage = null;

    public function getMessage()
    {
        if ($this->importStatus == 'success') {
            return $this->successMessage ?? "";
        } else {
            return $this->error ?? "";
        }
    }
}
