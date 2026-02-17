<?php

namespace App\ValueObjects;

use InvalidArgumentException;

class DocumentMeta
{
    public function __construct(
        public readonly string $viewExtends,
        public readonly string $title,
        public readonly int|string $createdAt,
        private readonly array $extra = [],
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $viewExtends = self::extractRequiredString($data, 'view::extends');
        $title = self::extractRequiredString($data, 'title');
        $createdAt = self::extractRequiredScalar($data, 'created_at');

        unset($data['view::extends'], $data['title'], $data['created_at']);

        return new self(
            viewExtends: $viewExtends,
            title: $title,
            createdAt: $createdAt,
            extra: $data,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'view::extends' => $this->viewExtends,
            'title' => $this->title,
            'created_at' => $this->createdAt,
            ...$this->extra,
        ];
    }

    public function get(string $key): mixed
    {
        return match ($key) {
            'view::extends' => $this->viewExtends,
            'title' => $this->title,
            'created_at' => $this->createdAt,
            default => $this->extra[$key] ?? null,
        };
    }

    public function __get(string $name): mixed
    {
        return $this->get($name);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function extractRequiredString(array $data, string $key): string
    {
        $value = self::extractRequiredScalar($data, $key);
        $stringValue = trim((string) $value);

        if ($stringValue === '') {
            throw new InvalidArgumentException("Meta field '{$key}' must not be empty.");
        }

        return $stringValue;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function extractRequiredScalar(array $data, string $key): int|string
    {
        if (! array_key_exists($key, $data)) {
            throw new InvalidArgumentException("Meta field '{$key}' is required.");
        }

        $value = $data[$key];

        if (! is_scalar($value)) {
            throw new InvalidArgumentException("Meta field '{$key}' must be scalar.");
        }

        if (is_int($value)) {
            return $value;
        }

        return (string) $value;
    }
}
