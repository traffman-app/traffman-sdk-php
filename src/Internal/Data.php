<?php

declare(strict_types=1);

namespace Traffman\Sdk\Internal;

use DateTimeImmutable;
use Exception;
use Traffman\Sdk\Exception\UnexpectedResponseException;

/**
 * Typed reader of a decoded response, used by the models.
 *
 * A missing field or a field of a wrong type raises {@see UnexpectedResponseException} with the path of the field.
 *
 * @internal
 */
final class Data
{
    /**
     * @param array<mixed> $data
     */
    public function __construct(
        private readonly array $data,
        private readonly string $path = '',
    ) {
    }

    /**
     * @return array<mixed>
     */
    public function all(): array
    {
        return $this->data;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data) && $this->data[$key] !== null;
    }

    public function string(string $key): string
    {
        $value = $this->data[$key] ?? null;

        return is_string($value) ? $value : throw $this->createException($key, 'a string');
    }

    public function nullableString(string $key): ?string
    {
        $value = $this->data[$key] ?? null;

        return $value === null || is_string($value) ? $value : throw $this->createException($key, 'a string or null');
    }

    public function int(string $key): int
    {
        $value = $this->data[$key] ?? null;

        return is_int($value) ? $value : throw $this->createException($key, 'an integer');
    }

    public function float(string $key): float
    {
        $value = $this->data[$key] ?? null;

        return is_int($value) || is_float($value) ? (float) $value : throw $this->createException($key, 'a number');
    }

    public function bool(string $key): bool
    {
        $value = $this->data[$key] ?? null;

        return is_bool($value) ? $value : throw $this->createException($key, 'a boolean');
    }

    public function dateTime(string $key): DateTimeImmutable
    {
        return $this->parseDateTime($key, $this->string($key));
    }

    public function nullableDateTime(string $key): ?DateTimeImmutable
    {
        $value = $this->nullableString($key);

        return $value !== null ? $this->parseDateTime($key, $value) : null;
    }

    /**
     * @return array<mixed>
     */
    public function array(string $key): array
    {
        $value = $this->data[$key] ?? null;

        return is_array($value) ? $value : throw $this->createException($key, 'an array');
    }

    /**
     * @return string[]
     */
    public function stringList(string $key): array
    {
        $result = [];

        foreach ($this->array($key) as $index => $value) {
            $result[] = is_string($value) ? $value : throw $this->createException($key . '.' . $index, 'a string');
        }

        return $result;
    }

    public function object(string $key): self
    {
        return new self($this->array($key), $this->buildPath($key));
    }

    public function nullableObject(string $key): ?self
    {
        return $this->has($key) ? $this->object($key) : null;
    }

    /**
     * @return list<self>
     */
    public function objectList(string $key): array
    {
        $result = [];

        foreach (array_values($this->array($key)) as $index => $value) {
            $result[] = is_array($value)
                ? new self($value, $this->buildPath($key . '.' . $index))
                : throw $this->createException($key . '.' . $index, 'an object');
        }

        return $result;
    }

    /**
     * Maps a list of objects with the factory of a model.
     *
     * @template T
     *
     * @param callable(self): T $factory
     *
     * @return list<T>
     */
    public function mapList(string $key, callable $factory): array
    {
        return array_map($factory, $this->objectList($key));
    }

    private function parseDateTime(string $key, string $value): DateTimeImmutable
    {
        try {
            return new DateTimeImmutable($value);
        } catch (Exception $e) {
            throw new UnexpectedResponseException(
                sprintf('The response field "%s" is not a valid date: "%s".', $this->buildPath($key), $value),
                $e,
            );
        }
    }

    private function createException(string $key, string $expected): UnexpectedResponseException
    {
        return new UnexpectedResponseException(
            sprintf('The response field "%s" is expected to be %s.', $this->buildPath($key), $expected),
        );
    }

    private function buildPath(string $key): string
    {
        return $this->path === '' ? $key : $this->path . '.' . $key;
    }
}
