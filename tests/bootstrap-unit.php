<?php

declare(strict_types=1);

// Load the module's own autoloader
require_once(__DIR__ . "/../vendor/autoload.php");

// Load the parent OpenEMR autoloader for OpenEMR core classes (FHIR, validators, etc.)
$openemrAutoloader = __DIR__ . "/../../../../../vendor/autoload.php";
if (file_exists($openemrAutoloader)) {
    require_once($openemrAutoloader);
}
