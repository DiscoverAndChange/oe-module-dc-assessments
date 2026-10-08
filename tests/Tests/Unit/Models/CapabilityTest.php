<?php

declare(strict_types=1);

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Pure-unit (no DB) characterization tests for the Capability constant enum.
 *
 * NOTE: src/Models/Capability.php declares the class in the GLOBAL namespace
 * (no `namespace` statement), so it is NOT reachable through the module's PSR-4
 * autoloader. We require the source file directly so the test can reference the
 * global \Capability symbol.
 */
class CapabilityTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        if (!class_exists(\Capability::class, false)) {
            require_once __DIR__ . '/../../../../src/Models/Capability.php';
        }
    }

    public function testConstantValues(): void
    {
        $this->assertSame(1, \Capability::CAN_SHOP);
        $this->assertSame(2, \Capability::CAN_BUY_TOKENS);
    }

    public function testCapabilitiesAreDistinct(): void
    {
        $this->assertNotSame(\Capability::CAN_SHOP, \Capability::CAN_BUY_TOKENS);
    }

    public function testClassIsAbstract(): void
    {
        $reflection = new \ReflectionClass(\Capability::class);
        $this->assertTrue($reflection->isAbstract());
    }
}
