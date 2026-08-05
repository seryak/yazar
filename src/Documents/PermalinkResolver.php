<?php

namespace Yazar\Documents;

final class PermalinkResolver
{
    /**
     * @param  array<string, string>  $tokens  keys without a leading ':', e.g. ['slug' => 'hello-world']
     */
    public static function resolve(string $pattern, array $tokens): string
    {
        $result = $pattern;

        foreach ($tokens as $token => $value) {
            $result = str_replace(":{$token}", $value, $result);
        }

        return trim($result, '/');
    }
}
