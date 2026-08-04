<?php

namespace Yazar\Build;

use Closure;

interface BuildProfiler
{
    /**
     * @template T
     *
     * @param  Closure(): T  $work
     * @return T
     */
    public function stage(string $name, Closure $work): mixed;
}
