<?php

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Services;

use OpenEMR\Common\Database\QueryUtils;

class TokenRepository
{
    const TABLE_VIEW_NAME = "dac_view_user_tokens";
    public function getUserTokens($userId)
    {
        return []; // we've removed user tokens for now, that will come in a later version
    }
}
