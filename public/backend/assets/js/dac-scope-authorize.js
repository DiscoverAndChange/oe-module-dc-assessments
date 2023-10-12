(function() {
    function initScopeAuthorize() {
        let document = window.document;
        $btnDeny = document.querySelector(".btn-scope-deny");
        if ($btnDeny) {
            $btnDeny.addEventListener("click", function (evt) {
                // uncheck everything
                document.querySelectorAll("input[type='checkbox']").forEach(function ($input) {
                    $input.checked = false;
                });
                // now we return normally to submit the form.
            });
        }

        // now we need to handle the individual resources
        $resourceCheckboxes = document.querySelectorAll('input.app-simple-scope');
        console.log($resourceCheckboxes);
        $resourceCheckboxes.forEach(function ($checkbox) {
            $checkbox.addEventListener("click", function (evt) {
                let $checkbox = evt.target;
                let resource = $checkbox.value;
                let $relatedScopes = document.querySelectorAll("input[data-resource='" + resource + "']");
                $relatedScopes.forEach(function ($relatedScope) {
                    $relatedScope.checked = $checkbox.checked;
                });
            });
        });
    }
    window.addEventListener("DOMContentLoaded", initScopeAuthorize);
})(window);
