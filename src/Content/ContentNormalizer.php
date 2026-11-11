<?php

declare(strict_types=1);

namespace Traffman\Sdk\Content;

use LogicException;
use Traffman\Sdk\Model\ContentItem;

/**
 * Turns HTML (or any text) into plain text accepted by the API for a {@see ContentItem}.
 *
 * Platform-specific markup (e.g. WordPress shortcodes) should be removed before.
 * Requires ext-mbstring.
 */
final class ContentNormalizer
{
    public function __construct()
    {
        if (!extension_loaded('mbstring')) {
            throw new LogicException('ContentNormalizer requires the "mbstring" PHP extension.');
        }
    }

    /**
     * Strips the tags (with the content of `<script>` and `<style>`), decodes the HTML entities,
     * replaces invalid UTF-8, collapses the whitespace and cuts the text to the max length (in characters).
     */
    public function normalize(string $text, int $maxLength = ContentItem::CONTENT_MAX_LENGTH): string
    {
        $text = mb_scrub($text, 'UTF-8');
        $text = (string) preg_replace('#<(script|style)\b[^>]*>.*?</\1\s*>#is', ' ', $text);
        // a block tag separates words, so it is replaced with a space before the tags are stripped
        $text = (string) preg_replace('#<(br|/p|/div|/li|/h[1-6]|/tr|/td|/th)\b[^>]*>#i', ' ', $text);
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = (string) preg_replace('/\s+/u', ' ', $text);

        return mb_substr(trim($text), 0, max(0, $maxLength), 'UTF-8');
    }

    public function normalizeTitle(string $title): string
    {
        return $this->normalize($title, ContentItem::TITLE_MAX_LENGTH);
    }

    /**
     * Whether the normalized content is long enough to pass the validation of the API.
     */
    public function isContentLongEnough(string $content): bool
    {
        return mb_strlen($content, 'UTF-8') >= ContentItem::CONTENT_MIN_LENGTH;
    }
}
