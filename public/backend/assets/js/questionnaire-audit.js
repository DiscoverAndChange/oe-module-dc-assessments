(function(portalAudit, window) {
    let webroot = window.top.webroot_url || '';
    let moduleScriptPath = portalAudit.scriptPath || webroot + "/interface/modules/custom_modules/oe-module-dc-assessments/public/backend/";
    let csrfToken = portalAudit.csrfToken || "";
    console.log(csrfToken);

    function addCallbackToButton(selector, callback) {
        let btns = document.querySelectorAll(selector);
        if (btns && btns.length > 0) {
            btns.forEach(btn => {
                btn.addEventListener('click', callback);
            });
        } else {
            console.error("Failed to find DOM Node with selector ", selector);
        }
    }
    function chartAssignmentToEncounter(event) {
        // grab the questionnaire response information and submit it
        let selectedEncounterId = document.querySelector("[name='assignment-encounter-select']");
        if (!selectedEncounterId) {
            console.error("Failed to find DOM Node with selector ", "[name='assignment-encounter-select']");
            return;
        }
        let eid = +(selectedEncounterId.value);
        if ((isNaN(eid) || eid <= 0)) {
            alert(window.top.xl('Please select an encounter to store this assignment into.'));
            return;
        }

        let data = {
            auditRecordId: event.target.dataset.auditRecordId
            ,encounterId: eid
            ,csrfToken: csrfToken
        };
        console.log(window.opener);
        window.fetch(moduleScriptPath + "/questionnaire-audit.php?action=chart-assignment-to-encounter", {
            method: 'POST'
            ,headers: {
                'Content-Type': 'application/json'
            }
            ,body: JSON.stringify(data)
            ,redirect: 'manual'
        })
        .then(response => {
            return response.json();
        })
        .then(json => {
            if (json.error) {
                // we need to show an error message here
                alert(window.top.xl(json.error));
            } else if (window.opener.dlgclose) {
                window.opener.dlgclose();
            }
            console.info("Assignment charted to an encounter", json);
        })
        .catch(error => {
            window.top.xl("Failed to chart assignment to encounter.");
            console.error(error);
        });
    }
    function chartQuestionnaireToDocument(event) {
        console.log("chartQuestionnaireToDocument");
    }
    function showCard(selector) {
        return function(event) {
            let auditCharts = document.querySelectorAll('.audit-card-chart .card');
            if (auditCharts && auditCharts.length > 0) {
                auditCharts.forEach(chart => {
                    if (!chart.classList.contains('d-none')) {
                        chart.classList.add('d-none');
                    }
                });
            }
            let card = document.querySelector(selector);
            if (card) {
                card.classList.remove('d-none');
            } else {
                console.error("Failed to find DOM Node with selector ", selector);
            }
        }
    }
    window.document.addEventListener("DOMContentLoaded", function() {
        let btn = document.querySelector('.btn-launch-patient-encounter-chart');
        addCallbackToButton('.btn-launch-patient-encounter-chart', showCard('.card-chart-encounters'));
        addCallbackToButton('.btn-launch-patient-document-chart', showCard('.card-chart-documents'));
        addCallbackToButton('.btn-chart-encounter', chartAssignmentToEncounter);
        addCallbackToButton('.btn-chart-document', chartQuestionnaireToDocument);
    });

})(window.portalAudit || {}, window);
