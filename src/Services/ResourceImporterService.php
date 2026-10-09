<?php

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Services;

use OpenEMR\BC\ServiceContainer;
use OpenEMR\Common\Database\QueryUtils;
use OpenEMR\Modules\DiscoverAndChange\Assessments\DTO\LibraryAssetBlobDTO;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\Import\ImportLogEntry;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Validators\AssessmentValidator;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Validators\LibraryAssetBlobValidator;
use OpenEMR\Validators\ProcessingResult;

class ResourceImporterService
{
    /**
     * @var AssessmentRepository
     */
    private $assessmentRepository;

    /**
     * @var AssessmentGroupService
     */
    private $assessmentGroupService;

    /**
     * @var ImportLogEntry[]
     */
    private array $importLog = [];
    /**
     * @param string $resource
     * @param mixed $importerUserId
     * @return void
     */
    public function import(string $resource, $importerUserId)
    {
        /** @var array<string, array<mixed>> $resources */
        $resources = json_decode($resource, true, 512, JSON_THROW_ON_ERROR);
        $this->importResources($resources, $importerUserId);
    }
    /**
     * @param array<string, array<mixed>> $resources
     * @param mixed $importerUserId
     * @return void
     */
    public function importResources(array $resources, $importerUserId)
    {
        $index = 0;
        if (isset($resources['AssessmentBlob']) && $resources['AssessmentBlob'] !== []) {
            $this->importAssessmentBlobResources($resources['AssessmentBlob'], $index);
        }

        if (isset($resources['LibraryAsset']) && $resources['LibraryAsset'] !== []) {
            $this->importLibraryAssetResources($resources['LibraryAsset'], $importerUserId, $index);
        }

        if (isset($resources['AssessmentGroup']) && $resources['AssessmentGroup'] !== []) {
            $this->importAssessmentGroupResources($resources['AssessmentGroup'], $importerUserId, $index);
        }

        if (isset($resources['Report']) && $resources['Report'] !== []) {
            $this->importReports($resources['Report'], $importerUserId, $index);
        }
    }

    /**
     * @return AssessmentRepository
     */
    public function getAssessmentRepository()
    {
        if ($this->assessmentRepository === null) {
            $this->assessmentRepository = new AssessmentRepository(ServiceContainer::getLogger());
        }
        return $this->assessmentRepository;
    }

    /**
     * @param AssessmentRepository $repository
     * @return void
     */
    public function setAssessmentRepository(AssessmentRepository $repository)
    {
        $this->assessmentRepository = $repository;
    }

    /**
     * @return AssessmentGroupService
     */
    public function getAssessmentGroupService()
    {
        if ($this->assessmentGroupService === null) {
            $this->assessmentGroupService = new AssessmentGroupService();
        }
        return $this->assessmentGroupService;
    }

    /**
     * @param array<mixed> $assessmentBlobs
     * @param int $index
     * @return void
     */
    public function importAssessmentBlobResources(array $assessmentBlobs, &$index)
    {
        $validator = new AssessmentValidator();
        $repo = new AssessmentRepository(ServiceContainer::getLogger());
        foreach ($assessmentBlobs as $blob) {
            /** @var array<string, mixed> $blob */
            $logEntry = new ImportLogEntry();
            $logEntry->index = $index++;
            // make it look good if we need to debug
            $logEntry->importResource = json_encode($blob, JSON_PRETTY_PRINT);
            $logEntry->type = "AssessmentBlob";
            $logEntry->importStatus = "failure";
            $this->importLog[] = $logEntry;
            $validation = $validator->validate($blob, AssessmentValidator::DATABASE_INSERT_CONTEXT);
            if (!$validation->isValid()) {
                // $report was an undefined variable here (copy/paste bug); the
                // loop item in this method is $blob.
                $logEntry->error = "assessment " . ($blob['_name'] ?? '<unknown>') . ' ' . implode(" ", (array) $validation->getValidationMessages());
            } else if ($repo->existsAssessment($blob['_uid'])) {
                $logEntry->error = "Assessment already exists with uid " . $blob['_uid'];
            } else {
                $uid = $blob['_uid'];
                $name = $blob['_name'];
                $description = $blob['_description'];
                if (isset($blob['token']) && $blob['token'] !== '') {
                    // cleanup routine
                    unset($blob['token']);
                }
                try {
                    // no company id to link this assessment to in the import.
                    $repo->createAssessment($uid, $name, $description, $blob, null);
                    $logEntry->importStatus = "success";
                    $logEntry->successMessage = "Successfully imported assessment with uid " . $uid . " and name " . $name;
                } catch (\Exception $e) {
                    // $report was an undefined variable here (copy/paste bug).
                    $logEntry->error = "assessment " . ($blob['_name'] ?? '<unknown>') . ' ' . $e->getMessage();
                    $logEntry->importStatus = "failure";
                }
            }
        }
    }

    /**
     * @return ImportLogEntry[]
     */
    public function getLogEntries()
    {
        return $this->importLog;
    }

    /**
     * @param array<mixed> $assets
     * @param mixed $importerUserId
     * @param int $index
     * @return void
     */
    public function importLibraryAssetResources(array $assets, $importerUserId, &$index)
    {
        $validator = new LibraryAssetBlobValidator();
        $repo = new LibraryAssetBlobRepository(ServiceContainer::getLogger());
        foreach ($assets as $assetBlob) {
            /** @var array<string, mixed> $assetBlob */
            $logEntry = new ImportLogEntry();
            $logEntry->index = $index++;
            $logEntry->importResource = json_encode($assetBlob, JSON_PRETTY_PRINT);
            $logEntry->type = "LibraryAsset";
            $logEntry->importStatus = "failure";
            $this->importLog[] = $logEntry;
            $validation = $validator->validate($assetBlob, LibraryAssetBlobValidator::DATABASE_INSERT_CONTEXT);
            if (!$validation->isValid()) {
                $errorMessage = "asset " . ($assetBlob['title'] ?? '<unknown>') . ' ';
                /** @var array<string, mixed> $validationMessages */
                $validationMessages = $validation->getValidationMessages();
                foreach ($validationMessages as $key => $value) {
                    $errorMessage .= "Validation failed for key $key with messages " . implode(";", (array) $value) . ".";
                }
                $logEntry->error = $errorMessage;
            } else if ($repo->existsAsset($assetBlob['title'])) {
                $logEntry->error = "LibraryAsset already exists with title " . $assetBlob['title'];
            } else {
                $asset = new LibraryAssetBlobDTO();
                $asset->fromDTO($assetBlob);

                // make sure we sanitize the content
                $sanitizer = new HTMLSanitizer();
                $asset->setContent($sanitizer->sanitize((string) $asset->getContent()));
                $asset->setDescription($sanitizer->sanitize((string) $asset->getDescription()));
                $asset->setTitle($sanitizer->sanitize((string) $asset->getTitle()));

                try {
                    // no company id to link this assessment to in the import.
                    $repo->saveLibraryAssetBlob($asset, $importerUserId);
                    $logEntry->importStatus = "success";
                    $logEntry->successMessage = "Successfully imported asset with title " . $asset->getTitle();
                } catch (\Exception $e) {
                    $logEntry->error = "asset " . ($assetBlob['title'] ?? '<unknown>') . ' ' . $e->getMessage();
                    $logEntry->importStatus = "failure";
                }
            }
        }
    }

    /**
     * @param array<mixed> $groups
     * @param mixed $importerId
     * @param int $index
     * @return void
     */
    public function importAssessmentGroupResources(array $groups, $importerId, &$index)
    {
        $repo = $this->getAssessmentGroupService();
        $assessmentRepo = $this->getAssessmentRepository();
        foreach ($groups as $group) {
            /** @var array<string, mixed> $group */
            $logEntry = new ImportLogEntry();
            $logEntry->index = $index++;
            $logEntry->importResource = json_encode($group, JSON_PRETTY_PRINT);
            $logEntry->type = "AssessmentGroup";
            $logEntry->importStatus = "failure";
            $this->importLog[] = $logEntry;
            try {
                QueryUtils::startTransaction();
                $companyId = null;
                if ($repo->existsGroup($group['_name'], $companyId)) {
                    throw new \InvalidArgumentException("Group with name " . $group['_name'] . " already exists");
                }
                $createdGroup = $repo->createGroup($group['_name'], $companyId);
                /** @var array<mixed> $groupAssessments */
                $groupAssessments = $group['_assessments'];
                foreach ($groupAssessments as $uid) {
                    if (!$assessmentRepo->existsAssessment($uid)) {
                        throw new \InvalidArgumentException("Failed to find assessment with uid " . $uid);
                    }
                    $repo->addAssessmentToGroup($uid, $createdGroup->getId(), $companyId);
                }
                $logEntry->importStatus = 'success';
                $logEntry->successMessage = "Successfully imported group with name " . $group['_name'];
                QueryUtils::commitTransaction();
            } catch (\Exception $e) {
                QueryUtils::rollbackTransaction();
                $logEntry->error = "group " . ($group['_name'] ?? '<unknown>') . ' ' . $e->getMessage();
                $logEntry->importStatus = "failure";
            }
        }
    }

    /**
     * @param array<mixed> $reports
     * @param mixed $importerId
     * @param int $index
     * @return void
     */
    public function importReports(array $reports, $importerId, &$index)
    {
        $repo = new AssessmentReportRepository();
        $groupRepo = $this->getAssessmentGroupService();
        $assessmentRepo = $this->getAssessmentRepository();

        foreach ($reports as $report) {
            /** @var array<string, mixed> $report */
            $logEntry = new ImportLogEntry();
            $logEntry->index = $index++;
            $logEntry->importResource = json_encode($report, JSON_PRETTY_PRINT);
            $logEntry->type = "Report";
            $logEntry->importStatus = "failure";
            $this->importLog[] = $logEntry;
            $assessmentUid = null;
            $groupId = null;
            // Reports appear in two export conventions: the underscore form
            // (_id/_name/_data/_assessment/_assessmentgroup) used by the test fixtures and
            // the SPA export form (id/name/data/linkedAssessments[]/linkedGroup) used by
            // DiscoverAndChangeResources.json. Normalize both so either imports cleanly.
            /** @var string $reportId */
            $reportId = $report['_id'] ?? $report['id'] ?? '';
            /** @var string $reportName */
            $reportName = $report['_name'] ?? $report['name'] ?? '';
            /** @var array<string, mixed> $reportData */
            $reportData = (array) ($report['_data'] ?? $report['data'] ?? []);
            $linkedAssessmentUid = $report['_assessment'] ?? null;
            /** @var list<mixed> $linkedAssessments */
            $linkedAssessments = (array) ($report['linkedAssessments'] ?? []);
            if (($linkedAssessmentUid === null || $linkedAssessmentUid === '') && $linkedAssessments !== []) {
                // the schema links a report to a single assessment; take the first
                $linkedAssessmentUid = $linkedAssessments[0] ?? null;
            }
            $linkedGroupName = $report['_assessmentgroup'] ?? $report['linkedGroup'] ?? null;
            try {
                QueryUtils::startTransaction();
                if ($linkedAssessmentUid !== null && $linkedAssessmentUid !== '') {
                    if (!$assessmentRepo->existsAssessment($linkedAssessmentUid)) {
                        throw new \InvalidArgumentException("Failed to find assessment with uid " . $linkedAssessmentUid);
                    }
                    $assessmentUid = $linkedAssessmentUid;
                } else if ($linkedGroupName !== null && $linkedGroupName !== '') {
                    $result = $groupRepo->search(['name' => $linkedGroupName]);
                    if (!$result->hasData()) {
                        throw new \InvalidArgumentException("Failed to find assessment group with name " . $linkedGroupName);
                    }
                    /** @var array<int, array<string, mixed>> $groupResultData */
                    $groupResultData = ProcessingResult::extractDataArray($result) ?? [];
                    $groupId = $groupResultData[0]['id'];
                } else {
                    throw new \InvalidArgumentException("Failed to find assessment or assessment group");
                }
                if ($repo->existsReport($reportId)) {
                    throw new \InvalidArgumentException("Report with id " . $reportId . " already exists");
                }
                $repo->createReport($reportId, $reportName, $importerId, $reportData, $groupId, $assessmentUid);
                QueryUtils::commitTransaction();
                $logEntry->importStatus = "success";
                $logEntry->successMessage = "Successfully imported report with title " . $reportName;
            } catch (\Exception $exception) {
                $logEntry->error = "report " . ($reportName ?: '<unknown>') . " " . $exception->getMessage() . " " . $exception->getTraceAsString();
                $logEntry->importStatus = "failure";
                QueryUtils::rollbackTransaction();
            }
        }
    }
}
