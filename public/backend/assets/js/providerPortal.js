(function() {
    // TODO: @adunsulag need to remove the hardcoded url path here
    let webroot = top.webroot_url || '';
    let url = webroot + '/interface/modules/custom_modules/oe-module-dc-assessments/public/backend/questionnaire-audit.php';
    var title = 'Audit Document';
    function showQuestionnairePortalAuditDialog(activityDetails) {
        let cpid = activityDetails.cpid;
        let questionnaireResponseId = activityDetails.modelAttributes.tableArgs;
        let recordId = activityDetails.modelAttributes.id;
        var params = {
            buttons: [
                {text: 'Help', close: false, style: 'info btn-sm', id: 'formHelp'},
                {text: 'Cancel', close: true, style: 'default btn-sm'},
                {text: 'Done', style: 'danger btn-sm', close: true}],
            sizeHeight: 'full',
            onClosed: 'reload',
            url: url + '?action=view&recordId=' + encodeURIComponent(recordId)
                + '&pid=' + encodeURIComponent(cpid)
                + '&qr=' + encodeURIComponent(questionnaireResponseId)
        };
        dlgopen('', '', 'modal-lg', '', '', '', params);
    }
    function showAssignmentPortalAuditDialog(activityDetails) {
        let cpid = activityDetails.cpid;
        let assignmentId = activityDetails.modelAttributes.tableArgs;
        let recordId = activityDetails.modelAttributes.id;
        var params = {
            buttons: [
                {text: 'Help', close: false, style: 'info btn-sm', id: 'formHelp'},
                {text: 'Cancel', close: true, style: 'default btn-sm'},
                {text: 'Done', style: 'danger btn-sm', close: true}],
            sizeHeight: 'full',
            onClosed: 'reload',
            url: url + '?action=view&recordId=' + encodeURIComponent(recordId)
                + '&pid=' + encodeURIComponent(cpid)
                + '&assignmentId=' + encodeURIComponent(assignmentId)
        };
        dlgopen('', '', 'modal-lg', '', '', '', params);
    }
    window.document.addEventListener("DOMContentLoaded", function() {
        window.document.body.addEventListener('openemr:portal:provider:onsiteactivityview:click', function(customEvent) {
            let details = customEvent.detail;;
            console.log("details received ", details);
            // here is where we can do our smart on fhir launch and handle the viewing and auditing of the questionnaire
            // we want to present an audit log of the questionnaire and let the provider chart it to the patient chart
            // as a specific document.
            if (details.activity == "questionnaire") {
                customEvent.preventDefault();
                showQuestionnairePortalAuditDialog(details);
                console.log("custom event fired", customEvent.detail);
            } else if (details.activity == 'dc-assignment') {
                // we are going to launch into our smart on fhir app here
                showAssignmentPortalAuditDialog(details);
            }
        });
    });
})(window);
