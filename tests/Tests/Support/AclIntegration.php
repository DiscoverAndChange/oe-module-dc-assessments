<?php

declare(strict_types=1);

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Tests\Support;

use OpenEMR\Common\Database\QueryUtils;
use OpenEMR\Common\Http\HttpRestRequest;
use OpenEMR\Common\Session\SessionWrapperFactory;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\ServerRestRequest;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\HeaderBag;

/**
 * Support trait for DB-backed integration tests of the ACL-gated REST controllers.
 *
 * The bare test DB ships with empty gacl_* tables, so AclMain::aclCheckCore() denies
 * everything and the controllers' create/update bodies are unreachable. loginAsAdmin()
 * installs OpenEMR's default ACL tree ONCE (idempotent; persists in the test DB) with the
 * seeded user (id=1, "phptestuser") in the Administrators group — which grants admin/super,
 * and aclCheckCore() short-circuits every check to allow for a super user — then sets the
 * active session user so the controllers see an authenticated admin.
 *
 * ServerRestRequest is final, so requests are built as a real instance wrapping a mocked
 * HttpRestRequest (getBodyAsJson() reads getContent() + the headers bag).
 *
 * @mixin \PHPUnit\Framework\TestCase
 */
trait AclIntegration
{
    protected static string $aclUser = 'phptestuser';
    protected const ACL_USER_ID = 1;

    protected function loginAsAdmin(): void
    {
        $have = QueryUtils::fetchSingleValue("SELECT id FROM gacl_aro_groups WHERE value = 'admin' LIMIT 1", 'id', []);
        if (empty($have)) {
            require_once $GLOBALS['fileroot'] . '/library/classes/Installer.class.php';
            $installer = new \Installer(
                ['iuser' => self::$aclUser, 'iuname' => 'PHPUnit Test User', 'iuserpass' => 'pw'],
                new NullLogger()
            );
            $installer->install_gacl();
        }
        $session = SessionWrapperFactory::getInstance()->getActiveSession();
        $session->set('authUser', self::$aclUser);
        $session->set('authUserID', self::ACL_USER_ID);
        $_SESSION['authUser'] = self::$aclUser;
        $_SESSION['authUserID'] = self::ACL_USER_ID;
    }

    /**
     * Build a ServerRestRequest carrying a JSON body (for create/update actions).
     *
     * @param array<string, mixed> $body
     */
    protected function jsonRequest(array $body, int $userId = self::ACL_USER_ID, string $uri = '/api/v1/x'): ServerRestRequest
    {
        $inner = $this->createMock(HttpRestRequest::class);
        $inner->method('getContent')->willReturn((string) json_encode($body));
        $inner->headers = new HeaderBag();
        $inner->method('getRequestUserId')->willReturn($userId);
        $inner->method('getUri')->willReturn($uri);
        $inner->method('isPatientRequest')->willReturn(false);
        return new ServerRestRequest($inner);
    }
}
