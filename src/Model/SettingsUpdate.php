<?php

declare(strict_types=1);

namespace Traffman\Sdk\Model;

use Traffman\Sdk\Enum\ContentLanguageMode;

/**
 * Change of the client settings. A null field is left unchanged; an empty list clears the setting.
 */
final class SettingsUpdate
{
    /**
     * @param ContentLanguageMode|null $contentLanguage Which language of the targeted content is accepted.
     * @param string[]|null $contentTopicTags Preferred topics of the targeted content (up to 99, 2–100 chars each).
     * @param string[]|null $blacklistDomains Domains whose content is never shown (up to 99, 4–100 chars each).
     * @param string[]|null $blacklistWords Content with these words is never shown (up to 99, 2–100 chars each).
     */
    public function __construct(
        public readonly ?ContentLanguageMode $contentLanguage = null,
        public readonly ?array $contentTopicTags = null,
        public readonly ?array $blacklistDomains = null,
        public readonly ?array $blacklistWords = null,
    ) {
    }

    public function isEmpty(): bool
    {
        return $this->toArray() === [];
    }

    /**
     * @return array<string, int|list<string>>
     */
    public function toArray(): array
    {
        $data = [
            'contentLanguage' => $this->contentLanguage?->value,
            'contentTopicTags' => self::normalizeList($this->contentTopicTags),
            'blacklistDomains' => self::normalizeList($this->blacklistDomains),
            'blacklistWords' => self::normalizeList($this->blacklistWords),
        ];

        return array_filter($data, static fn(mixed $value): bool => $value !== null);
    }

    /**
     * @param string[]|null $values
     *
     * @return list<string>|null
     */
    private static function normalizeList(?array $values): ?array
    {
        return $values !== null ? array_values(array_unique($values)) : null;
    }
}
