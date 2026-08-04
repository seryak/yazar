<?php

namespace Yazar\Build;

use Closure;

class NullBuildProfiler implements BuildProfiler
{
    public function stage(string $name, Closure $work): mixed
    {
        return $work();
    }
}
