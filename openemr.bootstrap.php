<?php

/**
 * Bootstrap custom module skeleton.  This file is an example custom module that can be used
 * to create modules that can be utilized inside the OpenEMR system.  It is NOT intended for
 * production and is intended to serve as the barebone requirements you need to get started
 * writing modules that can be installed and used in OpenEMR.
 *
 * @package   OpenEMR
 * @link      http://www.open-emr.org
 *
 * @author    Stephen Nielson <stephen@nielson.org>
 * @copyright Copyright (c) 2021 Stephen Nielson <stephen@nielson.org>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

namespace OpenEMR\Modules\DiscoverAndChange\Assessments;

// for now we will include our own library, at some point we want to include additional dependencies
require_once __DIR__ . '/vendor/autoload.php';

/**
 * @global OpenEMR\Core\ModulesClassLoader $classLoader
 */
// $classLoader->registerNamespaceIfNotExists('OpenEMR\\Modules\\DiscoverAndChange\\Assessments\\', __DIR__ . DIRECTORY_SEPARATOR . 'src');

/**
 * @global EventDispatcher $eventDispatcher Injected by the OpenEMR module loader;
 */
Bootstrap::instantiate($eventDispatcher, $GLOBALS['kernel']);
