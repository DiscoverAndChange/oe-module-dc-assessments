<?php

declare(strict_types=1);

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Tests\Uat\Browser;

use PHPUnit\Framework\TestCase;

/**
 * Base class for the browser (Playwright) UAT tier.
 *
 * Design (mirrors oe-module-ihi's BrowserUatTestCase, but the browser engine is Playwright instead
 * of Panther/Selenium): PHPUnit remains the entry point and the reporter. The PHP side does the
 * deterministic work -- opt-in gating, preflight, DB seeding and bounded teardown -- then shells out
 * to the Playwright runner (Node) for the actual browser interaction and parses its JSON report back
 * into PHPUnit assertions via {@see assertPlaywrightPassed()}.
 *
 * OPT-IN: set DC_BROWSER_UAT=1. Otherwise every test self-skips, so a bare `composer test` is
 * unaffected (this tier also lives outside the default `tests/Tests` testsuite and carries
 * #[Group('browser')]).
 *
 * PREFLIGHT (in order, each a specific skip message): opt-in flag -> Playwright project installed
 * -> app base URL reachable (TCP) -> database reachable + module active. A fresh stack that is not
 * up / not provisioned therefore SKIPS with an actionable message rather than failing.
 *
 * SEEDING / TEARDOWN: done over a raw PDO against the TARGET stack's DB (DC_DB_* env). A baseline
 * max(pid) is captured once per run; tearDownAfterClass() deletes only rows created this run
 * (pid > baseline), child tables first, swallowing errors -- it never touches pre-existing data.
 */
abstract class BrowserUatTestCase extends TestCase
{
    private const DEFAULT_BASE_URL = 'https://localhost:9300';

    /** '' = runnable, non-'' = skip reason, null = not yet computed. */
    private static ?string $skipReason = null;
    private static bool $bootstrapped = false;
    private static int $baselinePid = 0;
    private static ?\PDO $pdo = null;

    protected function setUp(): void
    {
        $reason = self::resolveSkipReason();
        if ($reason !== '') {
            $this->markTestSkipped($reason);
        }
        self::bootstrapRun();
    }

    // ---------------------------------------------------------------------------------------------
    // Gating + preflight
    // ---------------------------------------------------------------------------------------------

    private static function resolveSkipReason(): string
    {
        if (self::$skipReason !== null) {
            return self::$skipReason;
        }

        $flag = getenv('DC_BROWSER_UAT');
        if ($flag === false || $flag === '' || $flag === '0') {
            return self::$skipReason = 'Browser UAT tier is off. Set DC_BROWSER_UAT=1 (and bring the '
                . 'stack up) to run. See tests/Uat/Browser/README.md.';
        }

        // Playwright project installed?
        if (!is_file(self::playwrightDir() . '/node_modules/.bin/playwright')) {
            return self::$skipReason = 'Browser UAT enabled but Playwright is not installed. Run '
                . '`npm ci && npx playwright install chromium` in ' . self::playwrightDir() . '.';
        }

        // App reachable?
        [$host, $port] = self::baseUrlHostPort();
        if (!self::tcpReachable($host, $port)) {
            return self::$skipReason = 'Browser UAT enabled but the app is not reachable at '
                . self::baseUrl() . ' (' . $host . ':' . $port . ').';
        }

        // DB reachable + module active?
        if (($gap = self::provisioningGap()) !== '') {
            return self::$skipReason = $gap;
        }

        return self::$skipReason = '';
    }

    private static function provisioningGap(): string
    {
        try {
            $active = self::scalar("SELECT mod_active FROM modules WHERE mod_directory = 'oe-module-dc-assessments'");
        } catch (\Throwable $e) {
            return '[dc-uat] Preflight could not reach the database (' . $e->getMessage()
                . '). Check DC_DB_* env and that the DB is up. See tests/Uat/Browser/README.md.';
        }
        if ($active !== '1') {
            return '[dc-uat] Module oe-module-dc-assessments is not active in the target stack '
                . '(modules.mod_active=' . ($active ?? 'missing') . '). Enable it in Modules admin.';
        }
        return '';
    }

    private static function tcpReachable(string $host, int $port): bool
    {
        $conn = @fsockopen($host, $port, $errno, $errstr, 3.0);
        if (is_resource($conn)) {
            fclose($conn);
            return true;
        }
        return false;
    }

    // ---------------------------------------------------------------------------------------------
    // Run lifecycle + bounded teardown
    // ---------------------------------------------------------------------------------------------

    private static function bootstrapRun(): void
    {
        if (self::$bootstrapped) {
            return;
        }
        self::$baselinePid = (int) self::scalar('SELECT COALESCE(MAX(id), 0) FROM patient_data');
        self::$bootstrapped = true;
    }

    public static function tearDownAfterClass(): void
    {
        if (!self::$bootstrapped) {
            return;
        }
        self::cleanupRunPatients();
        self::$bootstrapped = false;
    }

    /** Delete only patients (and their dependent rows) created this run; child tables first. */
    private static function cleanupRunPatients(): void
    {
        $baseline = self::$baselinePid;
        try {
            $pdo = self::pdo();
            // module child rows keyed on the patient's id/pid (fixtures force pid == id)
            $pdo->exec("DELETE ai FROM dac_AssignmentItem ai JOIN dac_Assignment a ON ai.assignment_id = a.id JOIN patient_data p ON a.client_id = p.id WHERE p.id > {$baseline}");
            $pdo->exec("DELETE a FROM dac_Assignment a JOIN patient_data p ON a.client_id = p.id WHERE p.id > {$baseline}");
            $pdo->exec("DELETE pao FROM patient_access_onsite pao JOIN patient_data p ON pao.pid = p.pid WHERE p.id > {$baseline}");
            $count = $pdo->exec("DELETE FROM patient_data WHERE id > {$baseline}");
            fwrite(STDERR, "[dc-uat] teardown removed {$count} run-created patient(s) (id > {$baseline}).\n");
        } catch (\Throwable $e) {
            fwrite(STDERR, "[dc-uat] teardown cleanup skipped: {$e->getMessage()}\n");
        }
    }

    // ---------------------------------------------------------------------------------------------
    // Seeding (raw PDO against the target stack DB)
    // ---------------------------------------------------------------------------------------------

    /**
     * Seed a throwaway patient with ACTIVE portal credentials (the "verified" state the
     * portal_force_credential_reset='1' fix produces: portal_pwd_status=1, no onetime), so the
     * SMART patient login works first-time.
     *
     * @return array{pid: int, username: string, password: string}
     */
    protected static function seedPatientWithPortalCredentials(): array
    {
        $pdo = self::pdo();
        $suffix = bin2hex(random_bytes(4));
        $username = 'dcuat_' . $suffix;
        $password = 'DcUat-' . $suffix . '-Aa1!';

        $pdo->prepare(
            "INSERT INTO patient_data (fname, lname, date, pubpid, allow_patient_portal) "
            . "VALUES ('DcUat', ?, NOW(), ?, 'YES')"
        )->execute(['dcuat-' . $suffix, 'dcuat-' . $suffix]);
        $pid = (int) $pdo->lastInsertId();
        // the portal / assignment code keys client rows on pid == id
        $pdo->prepare("UPDATE patient_data SET pid = ? WHERE id = ?")->execute([$pid, $pid]);

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $pdo->prepare(
            "INSERT INTO patient_access_onsite "
            . "(pid, portal_username, portal_login_username, portal_pwd, portal_pwd_status, portal_onetime, date_created) "
            . "VALUES (?, ?, ?, ?, 1, NULL, NOW())"
        )->execute([$pid, $username, $username, $hash]);

        return ['pid' => $pid, 'username' => $username, 'password' => $password];
    }

    // ---------------------------------------------------------------------------------------------
    // Playwright bridge
    // ---------------------------------------------------------------------------------------------

    /**
     * Run the Playwright project (optionally filtered by -g/--grep) with the given extra env, and
     * return the parsed JSON report plus the process exit code.
     *
     * @param array<string, string> $extraEnv passed through to the specs (e.g. seeded credentials)
     * @param string|null           $grep     Playwright title filter (--grep)
     * @return array{exitCode: int, report: array<string, mixed>, raw: string}
     */
    protected static function runPlaywright(array $extraEnv = [], ?string $grep = null): array
    {
        $dir = self::playwrightDir();
        $reportFile = tempnam(sys_get_temp_dir(), 'dc-pw-') . '.json';

        $env = array_merge(getenv(), $extraEnv, [
            'DC_UAT_BASE_URL' => self::baseUrl(),
            'DC_E2E_REPORT'   => $reportFile,
            // keep Playwright from trying to download browsers mid-run
            'PLAYWRIGHT_SKIP_BROWSER_DOWNLOAD' => '1',
        ]);

        $cmd = ['./node_modules/.bin/playwright', 'test', '--reporter=json'];
        if ($grep !== null) {
            $cmd[] = '--grep';
            $cmd[] = $grep;
        }
        $cmdLine = implode(' ', array_map('escapeshellarg', $cmd));

        $descriptors = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $proc = proc_open('exec ' . $cmdLine, $descriptors, $pipes, $dir, $env);
        if (!is_resource($proc)) {
            throw new \RuntimeException('[dc-uat] Could not start Playwright (proc_open failed).');
        }
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($proc);

        // the JSON reporter writes to DC_E2E_REPORT (configured in playwright.config.ts); fall back
        // to stdout if the file is empty for any reason.
        $json = is_file($reportFile) ? (string) file_get_contents($reportFile) : '';
        @unlink($reportFile);
        if (trim($json) === '') {
            $json = (string) $stdout;
        }
        /** @var array<string, mixed> $report */
        $report = json_decode($json, true) ?: [];

        if ($report === [] && $exitCode !== 0) {
            fwrite(STDERR, "[dc-uat] Playwright produced no JSON report.\nSTDERR:\n" . $stderr . "\n");
        }

        return ['exitCode' => $exitCode, 'report' => $report, 'raw' => (string) $stderr];
    }

    /**
     * Translate a runPlaywright() result into PHPUnit assertions: fail with a readable list of the
     * failed specs if any test was "unexpected", otherwise pass. Skipped/fixme specs do not fail.
     *
     * @param array{exitCode: int, report: array<string, mixed>, raw: string} $result
     */
    protected function assertPlaywrightPassed(array $result): void
    {
        /** @var array<string, mixed> $stats */
        $stats = (is_array($result['report']['stats'] ?? null)) ? $result['report']['stats'] : [];
        $unexpected = (int) ($stats['unexpected'] ?? -1);
        $expected = (int) ($stats['expected'] ?? 0);

        if ($unexpected === -1) {
            $this->fail("Playwright did not report results (exit {$result['exitCode']}).\n" . $result['raw']);
        }

        $failures = self::collectFailingTitles($result['report']);
        $this->assertSame(
            0,
            $unexpected,
            "Playwright reported {$unexpected} failing browser test(s):\n - " . implode("\n - ", $failures)
        );
        $this->assertGreaterThan(0, $expected, 'Playwright ran no browser tests (check --grep / spec filters).');
    }

    /**
     * @param array<string, mixed> $report
     * @return list<string>
     */
    private static function collectFailingTitles(array $report): array
    {
        $titles = [];
        $walk = static function (array $suites) use (&$walk, &$titles): void {
            foreach ($suites as $suite) {
                foreach (($suite['specs'] ?? []) as $spec) {
                    $ok = (bool) ($spec['ok'] ?? true);
                    if (!$ok) {
                        $titles[] = (string) ($spec['title'] ?? 'unknown spec');
                    }
                }
                if (!empty($suite['suites']) && is_array($suite['suites'])) {
                    $walk($suite['suites']);
                }
            }
        };
        if (!empty($report['suites']) && is_array($report['suites'])) {
            $walk($report['suites']);
        }
        return $titles === [] ? ['(no per-spec detail in report)'] : $titles;
    }

    // ---------------------------------------------------------------------------------------------
    // Config helpers
    // ---------------------------------------------------------------------------------------------

    protected static function baseUrl(): string
    {
        $url = getenv('DC_UAT_BASE_URL');
        return ($url !== false && $url !== '') ? rtrim($url, '/') : self::DEFAULT_BASE_URL;
    }

    /** @return array{0: string, 1: int} */
    private static function baseUrlHostPort(): array
    {
        $parts = parse_url(self::baseUrl());
        $host = $parts['host'] ?? 'localhost';
        $port = (int) ($parts['port'] ?? (($parts['scheme'] ?? 'https') === 'https' ? 443 : 80));
        return [$host, $port];
    }

    protected static function playwrightDir(): string
    {
        $override = getenv('DC_PLAYWRIGHT_DIR');
        if ($override !== false && $override !== '') {
            return rtrim($override, '/');
        }
        return __DIR__ . '/playwright';
    }

    protected static function pdo(): \PDO
    {
        if (self::$pdo instanceof \PDO) {
            return self::$pdo;
        }
        $host = getenv('DC_DB_HOST') ?: 'localhost';
        $port = getenv('DC_DB_PORT') ?: '3306';
        $name = getenv('DC_DB_NAME') ?: 'openemr';
        $user = getenv('DC_DB_USER') ?: 'openemr';
        $pass = getenv('DC_DB_PASS') ?: 'openemr';
        return self::$pdo = new \PDO(
            "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4",
            $user,
            $pass,
            [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
        );
    }

    private static function scalar(string $sql): ?string
    {
        $stmt = self::pdo()->query($sql);
        $value = $stmt === false ? false : $stmt->fetchColumn();
        return $value === false ? null : (string) $value;
    }
}
