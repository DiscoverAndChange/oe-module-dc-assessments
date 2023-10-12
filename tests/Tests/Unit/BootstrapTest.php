<?php

/**
 * BootstrapTest.php
 * @package openemr
 * @link      http://www.open-emr.org
 * @author    Stephen Nielson <stephen@nielson.org>
 * @copyright Copyright (c) 2021 Stephen Nielson <stephen@nielson.org>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

/**
 * WebhookAPITest.php
 * @author    Stephen Nielson <stephen@nielson.org>
 */

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Tests;

use OpenEMR\Modules\DiscoverAndChange\Assessments\Bootstrap;
use Symfony\Component\EventDispatcher\EventDispatcher;
use PHPUnit\Framework\TestCase;

class BootstrapTest extends TestCase
{
    public function testSubscribeToEvents()
    {
        $eventDispatcher = new EventDispatcher();
        $bootstrap = new Bootstrap($eventDispatcher);
        $this->markTestIncomplete("TODO: Implement testSubscribeToEvents()");
    }
}
