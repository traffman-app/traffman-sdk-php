<?php

declare(strict_types=1);

namespace Traffman\Sdk\Model;

use Traffman\Sdk\Internal\Data;

/**
 * Content item to show to the site visitor.
 */
final class TargetedContentItem
{
    /**
     * @internal Build the model with {@see self::fromArray()}: the constructor arguments may change
     *           in a minor version.
     */
    public function __construct(
        /** ID of the content redirect (used in the redirect URL and its signature). */
        public readonly string $id,
        public readonly string $language,
        /** Plain text of the content. Escape it before output. */
        public readonly string $content,
        /** @var array<mixed> All the fields of the response data, including the ones the SDK does not map. */
        public readonly array $raw = [],
    ) {
    }

    /**
     * @param array<mixed> $data Decoded API response.
     */
    public static function fromArray(array $data): self
    {
        return self::fromData(new Data($data));
    }

    /**
     * @internal
     */
    public static function fromData(Data $data): self
    {
        return new self(
            $data->string('id'),
            $data->string('language'),
            $data->string('content'),
            $data->all(),
        );
    }
}
