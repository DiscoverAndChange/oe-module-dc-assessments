<?php

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Services;

use OpenEMR\Common\Database\QueryUtils;
use OpenEMR\Common\Logging\SystemLogger;
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
    public function import(string $resource, $importerUserId)
    {
        $resources = json_decode($resource, true, 512, JSON_THROW_ON_ERROR);
        $this->importResources($resources, $importerUserId);
    }
    public function importResources(array $resources, $importerUserId)
    {
        $index = 0;
        if (!empty($resources['AssessmentBlob'])) {
            $this->importAssessmentBlobResources($resources['AssessmentBlob'], $index);
        }

        if (!empty($resources['LibraryAsset'])) {
            $this->importLibraryAssetResources($resources['LibraryAsset'], $importerUserId, $index);
        }

        if (!empty($resources['AssessmentGroup'])) {
            $this->importAssessmentGroupResources($resources['AssessmentGroup'], $importerUserId, $index);
        }

        if (!empty($resources['Report'])) {
            $this->importReports($resources['Report'], $importerUserId, $index);
        }
    }

    public function getAssessmentRepository()
    {
        if (empty($this->assessmentRepository)) {
            $this->assessmentRepository = new AssessmentRepository(new SystemLogger());
        }
        return $this->assessmentRepository;
    }

    public function setAssessmentRepository(AssessmentRepository $repository)
    {
        $this->assessmentRepository = $repository;
    }

    public function getAssessmentGroupService()
    {
        if (empty($this->assessmentGroupService)) {
            $this->assessmentGroupService = new AssessmentGroupService();
        }
        return $this->assessmentGroupService;
    }

    public function importAssessmentBlobResources(array $assessmentBlobs, &$index)
    {
        $validator = new AssessmentValidator();
        $repo = new AssessmentRepository(new SystemLogger());
        foreach ($assessmentBlobs as $blob) {
            $logEntry = new ImportLogEntry();
            $logEntry->index = $index++;
            // make it look good if we need to debug
            $logEntry->importResource = json_encode($blob, JSON_PRETTY_PRINT);
            $logEntry->type = "AssessmentBlob";
            $logEntry->importStatus = "failure";
            $this->importLog[] = $logEntry;
            $validation = $validator->validate($blob, AssessmentValidator::DATABASE_INSERT_CONTEXT);
            if (!$validation->isValid()) {
                $logEntry->error = "assessment " . ($report['_name'] ?? '<unknown>') . ' ' . implode(" ", $validation->getValidationMessages());
            } else if ($repo->existsAssessment($blob['_uid'])) {
                $logEntry->error = "Assessment already exists with uid " . $blob['_uid'];
            } else {
                $uid = $blob['_uid'];
                $name = $blob['_name'];
                $description = $blob['_description'];
                if (!empty($blob['token'])) {
                    // cleanup routine
                    unset($blob['token']);
                }
                try {
                    // no company id to link this assessment to in the import.
                    $repo->createAssessment($uid, $name, $description, $blob, null);
                    $logEntry->importStatus = "success";
                    $logEntry->successMessage = "Successfully imported assessment with uid " . $uid . " and name " . $name;
                } catch (\Exception $e) {
                    $logEntry->error = "assessment " . ($report['_name'] ?? '<unknown>') . ' ' . $e->getMessage();
                    $logEntry->importStatus = "failure";
                }
            }
        }
    }

    public function getLogEntries()
    {
        return $this->importLog;
    }

    public function importLibraryAssetResources(array $assets, $importerUserId, &$index)
    {
        $validator = new LibraryAssetBlobValidator();
        $repo = new LibraryAssetBlobRepository(new SystemLogger());
        foreach ($assets as $assetBlob) {
            $logEntry = new ImportLogEntry();
            $logEntry->index = $index++;
            $logEntry->importResource = json_encode($assetBlob, JSON_PRETTY_PRINT);
            $logEntry->type = "LibraryAsset";
            $logEntry->importStatus = "failure";
            $this->importLog[] = $logEntry;
            $validation = $validator->validate($assetBlob, LibraryAssetBlobValidator::DATABASE_INSERT_CONTEXT);
            if (!$validation->isValid()) {
                $errorMessage = "asset " . ($assetBlob['title'] ?? '<unknown>') . ' ';
                foreach ($validation->getValidationMessages() as $key => $value) {
                    $errorMessage .= "Validation failed for key $key with messages " . implode(";", $value) . ".";
                }
                $logEntry->error = $errorMessage;
            } else if ($repo->existsAsset($assetBlob['title'])) {
                $logEntry->error = "LibraryAsset already exists with title " . $assetBlob['title'];
            } else {
                $asset = new LibraryAssetBlobDTO();
                $asset->fromDTO($assetBlob);

                // make sure we sanitize the content
                $sanitizer = new HTMLSanitizer();
                $asset->setContent($sanitizer->sanitize($asset->getContent()));
                $asset->setDescription($sanitizer->sanitize($asset->getDescription()));
                $asset->setTitle($sanitizer->sanitize($asset->getTitle()));

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

    public function importAssessmentGroupResources(array $groups, $importerId, &$index)
    {
        $repo = $this->getAssessmentGroupService();
        $assessmentRepo = $this->getAssessmentRepository();
        foreach ($groups as $group) {
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
                foreach ($group['_assessments'] as $uid) {
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

    public function importReports(array $reports, $importerId, &$index)
    {
        $repo = new AssessmentReportRepository();
        $groupRepo = $this->getAssessmentGroupService();
        $assessmentRepo = $this->getAssessmentRepository();

        foreach ($reports as $report) {
            $logEntry = new ImportLogEntry();
            $logEntry->index = $index++;
            $logEntry->importResource = json_encode($report, JSON_PRETTY_PRINT);
            $logEntry->type = "Report";
            $logEntry->importStatus = "failure";
            $this->importLog[] = $logEntry;
            $assessmentUid = null;
            $groupId = null;
            try {
                QueryUtils::startTransaction();
                if (!empty($report['assessment'])) {
                    if (!$assessmentRepo->existsAssessment($report['assessment'])) {
                        throw new \InvalidArgumentException("Failed to find assessment with uid " . $report['assessment']);
                    }
                    $assessmentUid = $report['assessment'];
                } else if (!empty($report['linkedGroup'])) {
                    $result = $groupRepo->search(['name' => $report['linkedGroup']]);
                    if (!$result->hasData()) {
                        throw new \InvalidArgumentException("Failed to find assessment group with name " . $report['linkedGroup']);
                    }
                    $groupId = ProcessingResult::extractDataArray($result)[0]['id'];
                } else {
                    throw new \InvalidArgumentException("Failed to find assessment or assessment group");
                }
                if ($repo->existsReport($report['id'])) {
                    throw new \InvalidArgumentException("Report with id " . $report['id'] . " already exists");
                }
                $repo->createReport($report['id'], $report['name'], $importerId, $report['data'], $groupId, $assessmentUid);
                QueryUtils::commitTransaction();
                $logEntry->importStatus = "success";
                $logEntry->successMessage = "Successfully imported report with title " . $report['name'];
            } catch (\Exception $exception) {
                $logEntry->error = "report " . ($report['name'] ?? '<unknown>') . " " . $exception->getMessage() . " " . $exception->getTraceAsString();
                $logEntry->importStatus = "failure";
                QueryUtils::rollbackTransaction();
            }
        }
    }
}
