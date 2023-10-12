<?php

/*
 *   Copyright (c) 2021 Discover and Change @author Stephen Nielson <snielson@discoverandchange.com>
 *   All rights reserved.
 *   For License information see LICENSE.md in the root folder of this project.
 */

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Models;

class Role
{
    const SuperUser = 1;
    const Owner = 2;
    const Admin = 3;
    const Registered = 4;
    const Client = 5;
    const UnAuthenticated = 6;

    private static $validRoles = [
        self::SuperUser,
        self::Owner,
        self::Admin,
        self::Registered,
        self::Client,
        self::UnAuthenticated
    ];

    public static function isValidRole(int $value): bool
    {
        return array_search($value, self::$validRoles) !== false;
    }
}
