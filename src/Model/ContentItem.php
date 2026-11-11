<?php

declare(strict_types=1);

namespace Traffman\Sdk\Model;

use DateTimeImmutable;
use DateTimeInterface;
use Traffman\Sdk\Exception\InvalidArgumentException;

/**
 * Content item (a post, an article, a page) pushed to the exchange.
 *
 * The API validates the item (see {@see PushResult::$failed}): the URL is an absolute HTTP(S) URL,
 * the title is 3–255 characters, the content is 128–4096 characters of plain text,
 * the update time is not later than an hour from now. Use {@see \Traffman\Sdk\Content\ContentNormalizer}
 * to turn HTML into plain text of the allowed length.
 */
final class ContentItem
{
    public const ID_MAX_LENGTH = 36;
    public const TITLE_MAX_LENGTH = 255;
    public const CONTENT_MIN_LENGTH = 128;
    public const CONTENT_MAX_LENGTH = 4096;

    public function __construct(
        /** External ID of the item on the site (1–36 characters), e.g. the post ID. */
        public readonly string $id,
        /** Public URL of the item. */
        public readonly string $url,
        /** Language code of the content (ISO 639-1, e.g. `en`, `uk`). */
        public readonly string $language,
        public readonly ?string $title,
        /** Plain text of the content. */
        public readonly string $content,
        /** Time of the last change of the item. */
        public readonly DateTimeInterface $updatedAt = new DateTimeImmutable(),
    ) {
        if ($id === '' || strlen($id) > self::ID_MAX_LENGTH) {
            throw new InvalidArgumentException(
                sprintf('The content item ID must be 1-%d characters long.', self::ID_MAX_LENGTH),
            );
        }
    }

    /**
     * @return array{id: string, url: string, language: string, title: string|null, content: string, updatedAt: string}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'url' => $this->url,
            'language' => $this->language,
            'title' => $this->title,
            'content' => $this->content,
            'updatedAt' => $this->updatedAt->format(DateTimeInterface::ATOM),
        ];
    }
}
