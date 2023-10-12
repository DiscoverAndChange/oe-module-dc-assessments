<?php

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Services;

use OpenEMR\Common\Database\QueryUtils;
use OpenEMR\Pdf\PatientPortalPDFDocumentCreator;
use OpenEMR\Services\QuestionnaireResponseService;

class QuestionnaireResponseOnSiteDocumentService
{
    public function __construct(private QuestionnaireResponseService $qrService)
    {
    }

    public function createDocument($templateId, $documentCategory, array $questionnaireResponse, $questionnaireName)
    {
        $pid = $questionnaireResponse['patient_id'];
        $formFilename = \convert_safe_file_dir_name($questionnaireResponse['response_id']) . '_' . \convert_safe_file_dir_name($pid) . '.pdf';

        $answers = $this->qrService->flattenQuestionnaireResponse($questionnaireResponse, '|', '');
        $content = $this->qrService->buildQuestionnaireResponseHtml($answers, '|');

        // we could leverage Jerry's document pdf code here if we could include the classes.
        $pdfCreator = new PatientPortalPDFDocumentCreator();
        $createdDocument =  $pdfCreator->createPdfDocument($pid, $formFilename, $documentCategory, $content);
        return $createdDocument;
        // now we want to create the onsite_documents

//        $onsiteId = $this->insertOnSiteDocumentRecord($templateId, $pid, $questionnaireResponse, $questionnaireName);
    }

    private function insertOnSiteDocumentRecord($templateId, $pid, $qr, $questionnaireName)
    {
        /**
         * @see /portal/patient/scripts/onsitedocuments.js
         'pid': cpid,
        'facility': page.formOrigin, // 0 portal, 1 dashboard, 2 patient documents
        'provider': page.onsiteDocument.get('provider'),
        'encounter': page.onsiteDocument.get('encounter'),
        'createDate': new Date(),
        'docType': page.onsiteDocument.get('docType'),
        'patientSignedStatus': ptsignature ? '1' : '0',
        'patientSignedTime': ptsignature ? new Date() : '0000-00-00',
        'authorizeSignedTime': page.onsiteDocument.get('authorizeSignedTime'),
        'acceptSignedStatus': page.onsiteDocument.get('acceptSignedStatus'),
        'authorizingSignator': page.onsiteDocument.get('authorizingSignator'),
        'reviewDate': (!isPortal) ? new Date() : '0000-00-00',
        'denialReason': page.onsiteDocument.get('denialReason'),
        'authorizedSignature': page.onsiteDocument.get('authorizedSignature'),
        'patientSignature': ptsignature,
        'fullDocument': templateContent,
        'fileName': page.onsiteDocument.get('fileName'),
        'filePath': page.onsiteDocument.get('filePath'),
        'csrf_token_form': csrfTokenDoclib
         */
        $values = [
            'pid' => $pid
            ,'facility' => 0 // we will treat saving through this service as coming from the dashboard
            ,'provider' => 0
            ,'encounter' => $qr['encounter']
            ,'create_date' => (new \DateTime())->format("Y-m-d H:i:s")
            ,'doc_type' => $questionnaireName
            ,'patient_signed_status' => 0
            ,'patient_signed_time' => '0000-00-00'
            ,'authorize_signed_time' => '0000-00-00'
            ,'accept_signed_status' => 0
            ,'authorizing_signator' => 0
            ,'review_date' => (new \DateTime())->format("Y-m-d H:i:s")
            ,'denial_reason' => 'In Review'
            ,'authorized_signature' => ''
            ,'patient_signature' => '' // TODO: @adunsulag should we populate the patient signature if we have one.
            ,'full_document' => $qr['questionnaire_response'] // we could leave this empty
            ,'file_name' => $questionnaireName
            ,'file_path' => $templateId // this is the template_id
        ];
        $columnBinding = str_repeat("?,", count($values) - 1) . "?";
        $sql = "INSERT INTO onsite_documents(pid, facility, provider, encounter, create_date, doc_type"
        . ", patient_signed_status, patient_signed_time, authorize_signed_time,accept_signed_status, authorizing_signator"
        . ",review_date,denial_reason,authorized_signature, patient_signature, full_document,file_name,file_path) "
        . " VALUES (" . $columnBinding . ") ";
        return QueryUtils::sqlInsert($sql, array_values($values));
    }
}
