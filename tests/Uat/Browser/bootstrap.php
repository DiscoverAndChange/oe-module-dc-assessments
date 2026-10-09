<?php

/**
 * Dedicated PHPUnit bootstrap for the browser (Playwright) UAT tier.
 *
 * Mirrors oe-module-ihi's tests/Uat/Browser/bootstrap.php: it loads OpenEMR CORE's autoloader
 * (PHPUnit lives there) and hand-registers ONLY the module's own namespaces from source. It must
 * NOT require the module's own vendor/autoload.php -- that would put a second PHPUnit on the class
 * path and collide. This tier does not initialise OpenEMR's DB globals; the base test case talks to
 * the target database over a raw PDO (see BrowserUatTestCase::pdo()), exactly like the ihi tier.
 *
 * Invoke via `composer uat:browser` (see composer.json), which runs core's phpunit with
 * `--no-configuration --bootstrap tests/Uat/Browser/bootstrap.php --group browser tests/Uat/Browser`.
 */

declare(strict_types=1);

// Core autoloader: PHPUnit, Symfony, PSR, Guzzle, etc. (module is deployed 4 levels under the
// OpenEMR webroot, so the webroot -- and its vendor/ -- is 7 levels up from this file).
$coreAutoload = dirname(__DIR__, 7) . '/vendor/autoload.php';
if (!is_file($coreAutoload)) {
    fwrite(STDERR, "[dc-uat] Could not find OpenEMR core autoloader at {$coreAutoload}.\n"
        . "The browser UAT tier must run from the module's DEPLOYED location inside the OpenEMR tree\n"
        . "(interface/modules/custom_modules/oe-module-dc-assessments). See tests/Uat/Browser/README.md.\n");
    exit(1);
}
require_once $coreAutoload;

$moduleRoot = dirname(__DIR__, 3);
// Most-specific prefix first: the loop returns on the first match.
$prefixes = [
    'OpenEMR\\Modules\\DiscoverAndChange\\Assessments\\Tests\\Uat\\' => $moduleRoot . '/tests/Uat/',
    'OpenEMR\\Modules\\DiscoverAndChange\\Assessments\\Tests\\'      => $moduleRoot . '/tests/Tests/',
    'OpenEMR\\Modules\\DiscoverAndChange\\Assessments\\'             => $moduleRoot . '/src/',
];
spl_autoload_register(static function (string $class) use ($prefixes): void {
    foreach ($prefixes as $prefix => $baseDir) {
        if (!str_starts_with($class, $prefix)) {
            continue;
        }
        $relative = substr($class, strlen($prefix));
        $file = $baseDir . str_replace('\\', '/', $relative) . '.php';
        if (is_file($file)) {
            require_once $file;
        }
        return;
    }
});
