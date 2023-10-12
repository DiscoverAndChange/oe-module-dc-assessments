<?php

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Services;

use OpenEMR\Common\Acl\AclMain;
use OpenEMR\Common\System\System;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\Role;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\SystemUser;
use OpenEMR\Services\FacilityService;
use OpenEMR\Services\PatientService;
use OpenEMR\Services\Search\TokenSearchField;
use OpenEMR\Services\UserService;

class SystemUserRepository
{
    /**
     * @return SystemUser[]
     */
    public function getUsers()
    {

        $userRepo = new UserService();
        $userRepo->toggleSensitiveFields(['username']);
        $users = $userRepo->getAll();
        // now we need to hydrate them and convert them to SystemUser classes
        $systemUsers = [];
        $facRepo = new FacilityService();
        $primaryEntity = $facRepo->getPrimaryBusinessEntity();
        foreach ($users as $user) {
            $systemUsers[] = $this->hydrateUser($user, $primaryEntity);
        }
        return $systemUsers;
    }
    public function hydrateUser(array $user, $primaryEntity)
    {
        $companyId = $primaryEntity['id'] ?? null;
        // we will treat the uuid as the username as we don't want to reveal that anymore to the frontend
        $systemUser = new SystemUser($user['uuid'], $user['username'], $companyId);
        if (AclMain::aclCheckCore('admin', 'super', $user['username'])) {
            $systemUser->setRole(Role::SuperUser);
        } else {
            // if we need to introduce the role of company admin's we can do that here, but not sure there is an ACL for that.
            $systemUser->setRole(Role::Registered);
        }
        $systemUser->setFirstName($user['fname'] ?? '');
        $systemUser->setLastName($user['lname'] ?? '');
        $systemUser->setCompanyName($primaryEntity['name'] ?? '');
        $systemUser->setEnabled($user['active'] == '1');
        // TODO: if we need different capabilities we can set that here.
        return $systemUser;
    }

    public function getUsersForClients(array $clientIds)
    {
        $patientService = new PatientService();
        $mappedProviderIds = $patientService->getProviderIDsForPatientUuids($clientIds);
        // tokens are required to be strings
        $userIds = array_map('strval', array_values($mappedProviderIds));
        $idSearch = new TokenSearchField('id', $userIds);
        $userRepo = new UserService();
        $userRepo->toggleSensitiveFields(['username']);
        $users = $userRepo->getAll(['id' => $idSearch]);
        $mappedProviderUuids = [];
        foreach ($users as $user) {
            $mappedProviderUuids[$user['uuid']] = $user['id'];
        }

        // now we need to hydrate them and convert them to SystemUser classes
        $systemUsers = [];
        $facRepo = new FacilityService();
        $primaryEntity = $facRepo->getPrimaryBusinessEntity();
        $userIdIndex = [];
        foreach ($users as $user) {
            $systemUser = $this->hydrateUser($user, $primaryEntity);
            $providerId = $mappedProviderUuids[$systemUser->getId()];
            $userIdIndex[$providerId] = $systemUser;
        }
        // we have uuid => systemUser
        // we have clientId => id
        // we need a mapping from user.id => providerID
        $resultClientSystemUserMap = [];
        foreach ($mappedProviderIds as $clientId => $providerId) {
            if (!empty($userIdIndex[$providerId])) {
                $resultClientSystemUserMap[$clientId] = $userIdIndex[$providerId];
            }
        }
        return $resultClientSystemUserMap;
    }
}
