<?php

namespace Tests\Unit\Build;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use Yazar\Build\NullBuildProfiler;

#[CoversClass(NullBuildProfiler::class)]
class NullBuildProfilerTest extends TestCase
{
    #[TestDox('stage() calls the given closure exactly once')]
    public function test_stage_calls_the_closure_exactly_once(): void
    {
        $calls = 0;

        (new NullBuildProfiler)->stage('import', function () use (&$calls) {
            $calls++;

            return null;
        });

        $this->assertSame(1, $calls);
    }

    #[TestDox('stage() returns the closure result unchanged')]
    public function test_stage_returns_the_closure_result_unchanged(): void
    {
        $result = (new NullBuildProfiler)->stage('export', fn () => 42);

        $this->assertSame(42, $result);
    }
}
