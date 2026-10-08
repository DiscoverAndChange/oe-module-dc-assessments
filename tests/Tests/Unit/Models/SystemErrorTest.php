<?php

declare(strict_types=1);

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Tests;

use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\SystemError;
use PHPUnit\Framework\TestCase;

/**
 * Pure-unit characterization tests for SystemError, a RuntimeException subclass
 * carrying an application error code and optional nested sub-errors.
 */
class SystemErrorTest extends TestCase
{
    public function testConstructorSetsMessageAndCode(): void
    {
        $err = new SystemError(5001, 'boom');

        $this->assertSame('boom', $err->getMessage());
        $this->assertSame(5001, $err->code());
        // parent RuntimeException also receives the code as its exception code.
        $this->assertSame(5001, $err->getCode());
    }

    public function testIsRuntimeExceptionAndThrowable(): void
    {
        $err = new SystemError(4000, 'bad request');

        $this->assertInstanceOf(\RuntimeException::class, $err);
        $this->assertInstanceOf(\Throwable::class, $err);
    }

    public function testSubErrorsDefaultsToEmptyArray(): void
    {
        $err = new SystemError(4000, 'bad request');
        $this->assertSame([], $err->subErrors());

        $errExplicitNull = new SystemError(4000, 'bad request', null);
        $this->assertSame([], $errExplicitNull->subErrors(), 'null => empty array');
    }

    public function testSubErrorsArePreserved(): void
    {
        $sub1 = new SystemError(4003, 'missing field a');
        $sub2 = new SystemError(4003, 'missing field b');
        $err = new SystemError(4004, 'validation failed', [$sub1, $sub2]);

        $subs = $err->subErrors();
        $this->assertCount(2, $subs);
        $this->assertSame($sub1, $subs[0]);
        $this->assertSame($sub2, $subs[1]);
        $this->assertSame('missing field a', $subs[0]->getMessage());
    }

    public function testCanBeThrownAndCaught(): void
    {
        $this->expectException(SystemError::class);
        $this->expectExceptionMessage('thrown');
        throw new SystemError(4041, 'thrown');
    }
}
