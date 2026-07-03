<?php

namespace Yazar\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use InvalidArgumentException;
use Yazar\ValueObjects\DocumentMeta;

/** @implements CastsAttributes<?DocumentMeta, mixed> */
class DocumentMetaCast implements CastsAttributes
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function get($model, string $key, $value, array $attributes): ?DocumentMeta
    {
        if ($value === null) {
            return null;
        }

        $decoded = json_decode((string) $value, true);

        if (! is_array($decoded)) {
            throw new InvalidArgumentException("The '{$key}' attribute must be a valid JSON object.");
        }

        return DocumentMeta::fromArray($decoded);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, string>
     */
    public function set($model, string $key, $value, array $attributes): array
    {
        if ($value === null) {
            return [$key => json_encode([], JSON_THROW_ON_ERROR)];
        }

        if ($value instanceof DocumentMeta) {
            return [$key => json_encode($value->toArray(), JSON_THROW_ON_ERROR)];
        }

        if (is_array($value)) {
            return [$key => json_encode(DocumentMeta::fromArray($value)->toArray(), JSON_THROW_ON_ERROR)];
        }

        throw new InvalidArgumentException("The '{$key}' attribute must be an instance of DocumentMeta or array.");
    }
}
